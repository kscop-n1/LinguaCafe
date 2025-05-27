from spacy.language import Language
import spacy
import TokenizerConfig
import importlib
import subprocess
import json
import os
import shutil

config = TokenizerConfig.TokenizerConfig()

@Language.component("custom_sentence_splitter")
def custom_sentence_splitter(doc):    
    punctuations = ['NEWLINE', '？', '！', '。', '?', '!', '.', '»', '«']
    for token in doc[:-1]:
        if token.text in punctuations:
            doc[token.i+1].is_sent_start = True
        else:
            doc[token.i+1].is_sent_start = False
    return doc

class PackageManagerService:
    def __init__(self):
        self.loaded_language_models = {}

    def list_installed_packages(self):
        try:
            # spacy
            result = subprocess.run(
                ["pip", "list"], capture_output=True, text=True, check=True
            )
            
            installed = result.stdout.splitlines()[2:]
            package_names = [pkg.split()[0] for pkg in installed if pkg.strip()]

            spacy_models = [config.spacy_model_name[lang] for lang in package_names if lang in config.spacy_model_name]
            
            # stanza
            if (os.path.exists("/var/www/html/storage/app/packages/language_models/stanza")):
                dir_list = os.listdir("/var/www/html/storage/app/packages/language_models/stanza") 
                stanza_models = [config.stanza_model_name[lang] for lang in dir_list if lang in config.stanza_model_name]
            else: 
                stanza_models = []
            
            # packages
            other_packages = []
            if "stanza" in package_names: 
                other_packages.append('stanza')

            installed_packages = {
                'spacy_models': spacy_models,
                'stanza_models': stanza_models,
                'packages': other_packages
            }

            return installed_packages
        except subprocess.CalledProcessError as e:
            return HTTPResponse(status=200, body=f"Error: {e}")

    def install_language_model(self, language, tokenizer):
        if tokenizer == 'stanza':
            import stanza
            stanza.download(language, model_dir='/var/www/html/storage/app/packages/language_models/stanza')

        if tokenizer == 'spacy': 
            try:
                subprocess.check_output(
                    [
                        "pip",
                        "install",
                        "--target=/var/www/html/storage/app/packages/language_models/spacy",
                        config.spacy_urls[language],
                    ]
                )
                
                if language == "Thai":
                    subprocess.check_output([
                        "pip",
                        "install",
                        "--target=/var/www/html/storage/app/packages/language_models/spacy",
                        "tzdata"])

                # https://stackoverflow.com/questions/78634235
                if language == "Turkish":
                    subprocess.check_output([
                        "pip",
                        "install",
                        "--target=/var/www/html/storage/app/packages/language_models/spacy",
                        "numpy<2.0.0",
                        "--upgrade"])

                # Refresh installed python packages in runtime
                importlib.invalidate_caches()
            except subprocess.CalledProcessError as e:
                return False
        return True

    def delete_installed_packages():
        shutil.rmtree("/var/www/html/storage/app/packages")

    def load_language_model(self, language, tokenizer):
        if 'stanza_' + language in self.loaded_language_models:
            return

        if tokenizer == 'stanza':
            import stanza
            self.loaded_language_models['stanza_' + language] = stanza.Pipeline(language, processors='tokenize,pos', download_method=None, model_dir='/var/www/html/storage/app/packages/language_models/stanza')

        if tokenizer == 'spacy':
            disabled_parameters = ['ner'] if language in ('welsh', 'czech', 'latin', 'german') else ['ner', 'parser']

            if language in ('welsh', 'czech', 'latin'):
                self.loaded_language_models['spacy_' + language] = spacy.load(config.spacy_models[language], disable = disabled_parameters)
            elif language == 'thai': 
                import spacy_thai
                self.loaded_language_models['spacy_' + language] = spacy_thai.load()
            else:
                self.loaded_language_models['spacy_' + language] = spacy.load(config.spacy_models['multi'], disable = disabled_parameters)
        
            self.loaded_language_models['spacy_' + language].add_pipe("custom_sentence_splitter", first=True)
    
    def get_language_model(self, language, tokenizer):
        self.load_language_model(language, tokenizer)
        return self.loaded_language_models[tokenizer + '_' + language]