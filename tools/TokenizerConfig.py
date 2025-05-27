class TokenizerConfig:
    def __init__(self):
        self.spacy_urls = {
            "Japanese": "https://github.com/explosion/spacy-models/releases/download/ja_core_news_sm-3.7.0/ja_core_news_sm-3.7.0-py3-none-any.whl",
            "Korean": "https://github.com/explosion/spacy-models/releases/download/ko_core_news_sm-3.7.0/ko_core_news_sm-3.7.0-py3-none-any.whl",
            "Russian": "https://github.com/explosion/spacy-models/releases/download/ru_core_news_sm-3.7.0/ru_core_news_sm-3.7.0-py3-none-any.whl",
            "Ukrainian": "https://github.com/explosion/spacy-models/releases/download/uk_core_news_sm-3.7.0/uk_core_news_sm-3.7.0-py3-none-any.whl",
            "Chinese": "https://github.com/explosion/spacy-models/releases/download/zh_core_web_sm-3.7.0/zh_core_web_sm-3.7.0-py3-none-any.whl",
            "Turkish": "https://huggingface.co/turkish-nlp-suite/tr_core_news_md/resolve/main/tr_core_news_md-1.0-py3-none-any.whl",
            "Thai": "spacy_thai",
        }

        self.spacy_models = {
            'german': "de_core_news_sm",
            'japanese': "ja_core_news_sm",
            'korean': "ko_core_news_sm",
            'norwegian': "nb_core_news_sm",
            'spanish': "es_core_news_sm",
            'chinese': "zh_core_web_sm",
            'dutch': "nl_core_news_sm",
            'finnish': "fi_core_news_sm",
            'french': "fr_core_news_sm",
            'italian': "it_core_news_sm",
            'russian': "ru_core_news_sm",
            'swedish': "sv_core_news_sm",
            'ukrainian': "uk_core_news_sm",
            'english': "en_core_web_sm",
            'greek': "el_core_news_sm",
            'turkish': "tr_core_news_md",
            'catalan': "ca_core_news_sm",
            'croatian': "hr_core_news_sm",
            'danish': "da_core_news_sm",
            'lithuanian': "lt_core_news_sm",
            'macedonian': "mk_core_news_sm",
            'polish': "pl_core_news_sm",
            'portuguese': "pt_core_news_sm",
            'romanian': "ro_core_news_sm",
            'slovenian': "sl_core_news_sm",
            'multi': "xx_ent_wiki_sm",
        }

        self.spacy_model_name = {
            "ja-core-news-sm": "Japanese",
            "ko-core-news-sm": "Korean",
            "ru-core-news-sm": "Russian",
            "uk-core-news-sm": "Ukrainian",
            "zh-core-web-sm": "Chinese",
            "tr-core-news-md": "Turkish",
            "spacy-thai": "Thai",
        }

        self.stanza_model_name = {
            "bg": "Bulgarian",
            "vi": "Vietnamese",
        }