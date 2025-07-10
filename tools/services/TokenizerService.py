import pykakasi
from . import TokenizerConfig

config = TokenizerConfig.TokenizerConfig()

class TokenizerService:
    def __init__(self, packageManagerService):
        self.packageManagerService = packageManagerService

    # text: string or array of strings to be tokenized
    def tokenizePlainText(self, text, language, tokenizer):
        if type(text) == str:
            words = self.tokenizeString(text, language, tokenizer)
            return words
        else:
            tokenizedText = list()
            for text in text:
                tokenizedText.append(self.tokenizeString(text, language, tokenizer))
            return tokenizedText

    def cutAndTokenizePlainText(self, text, language, chunkSize):
        text = text.replace('\r\n', ' NEWLINE ')
        text = text.replace('\n', ' NEWLINE ')

        for sentenceEnding in config.sentenceEndings:
            text = text.replace(sentenceEnding, sentenceEnding + 'TMP_ST')
        sentences = text.split('TMP_ST')

        chunks = list()
        for sentenceIndex, sentence in enumerate(sentences):
            if (len(chunks) == 0 or len(chunks[-1].replace(' NEWLINE ', '')) > chunkSize):
                chunks.append('')

            chunks[-1] += sentences[sentenceIndex]
            chunks[-1] = chunks[-1].replace(' NEWLINE ', '\r\n')
            chunks[-1] = chunks[-1].replace('\xa0', ' ')

        return chunks

    def tokenizeSubtitles(self, subtitles, language, tokenizer):
        tokenizedText = list()
        timeStamps = list()

        currentChunkSentenceIndex = 0
        for subtitleIndex, subtitle in enumerate(subtitles):         
            text = subtitles[subtitleIndex]['text'].replace('\r\n', ' NEWLINE ')
            text = text.replace('\n', ' NEWLINE ')

            tokenizedSubtitle = self.tokenizeString(text, language, tokenizer, currentChunkSentenceIndex)

            currentChunkSentenceIndex = tokenizedSubtitle[-1]['si'] + 1

            timeStamps.append({
                'start': subtitles[subtitleIndex]['start'],
                'end': subtitles[subtitleIndex]['end'],
                'sentenceIndexStart': tokenizedSubtitle[0]['si'],
                'sentenceIndexEnd': tokenizedSubtitle[-1]['si']
            })
                        
            tokenizedText = tokenizedText + tokenizedSubtitle

        return {'tokenizedText': tokenizedText, 'timeStamps': timeStamps}
   
    def cutSubtitlesIntoChunks(self, subtitles, language, chunkSize):
        chunks = list()
        currentChunkSize = 0
        for subtitleIndex, subtitle in enumerate(subtitles):
            if (len(chunks) == 0 or currentChunkSize > chunkSize):
                currentChunkSize = 0
                chunks.append([])

        
            text = subtitles[subtitleIndex]['text'].replace('\r\n', ' NEWLINE ')
            text = text.replace('\n', ' NEWLINE ')

            currentChunkSize += len(text.replace(' NEWLINE ', ''))
            
            chunks[-1].append(subtitle)

        return chunks
        
    def tokenizeString(self, text, language, tokenizer, sentenceIndexStart = 0):
        # Mark thai new sentences. It is required because thai sentence indexing does not work in spacy.
        if language == 'thai':
            text = text.replace(' ', ' THAINEWSENTENCE ')
            
        language_model = self.packageManagerService.get_language_model(language, tokenizer)
        doc = language_model(text)

        if tokenizer == 'stanza':
            return self.transformStanzaDoc(doc)
        
        if tokenizer == 'spacy':
            return self.transformSpacyDoc(doc, language, sentenceIndexStart)

    def transformStanzaDoc(self, doc):
        tokenizedWords = list()
        sentenceIndex = 0
        for sentence in doc.sentences:
            for token in sentence.tokens:
                for wordIndex, word in enumerate(token.words):
                    isLastWord = (wordIndex == len(token.words) - 1)
                    
                    if word == " " or word == "" or word == " ":
                        continue

                    # space after
                    space_after = len(token.spaces_after) > 0
                    space_before = len(token.spaces_before) > 0

                    tokenizedWords.append(
                        {
                            "w": word.text,
                            "r": "",
                            "l": word.lemma,
                            "lr": "",
                            "pos": word.upos,
                            "si": sentenceIndex,
                            "g": "",
                            "ip": word.upos == "PUNCT",
                            "sb": space_before if wordIndex == 0 else False,
                            "sa": space_after if isLastWord else False,
                        }
                    )

            sentenceIndex += 1

        return tokenizedWords
        
    def transformSpacyDoc(self, doc, language, sentenceIndexStart):
        if language == 'japanese':
            global hiraganaConverter
            hiraganaConverter = pykakasi.kakasi()
        
        tokenizedWords = list()
        thaiSentenceIndex = 0
        space_before = False
        for sentenceIndex, sentence in enumerate(doc.sents):
            for token in sentence:
                word = str(token.text)
                if word == " " or word == "" or word == " ":
                    space_before = True
                    continue
                else:
                    space_before = False

                # get lemma
                lemma = token.lemma_
                
                # get hiragana reading
                reading = list()
                lemmaReading = list()
                if language == 'japanese':
                    result = hiraganaConverter.convert(token.text)
                    for x in result:
                        reading.append(x['hira'])
                    
                    result = hiraganaConverter.convert(token.lemma_)
                    for x in result:
                        lemmaReading.append(x['hira'])
                
                    reading = ''.join(reading)
                    lemmaReading = ''.join(lemmaReading)

                # get pinyin reading
                if language == 'chinese':
                    import pinyin
                    reading = pinyin.get(word)
                    lemmaReading = pinyin.get(lemma)

                # get gender
                gender = ''
                if language in ('norwegian', 'german'):
                    gender = token.morph.get("Gender")

                if language == 'german' and token.pos_ == 'VERB':
                    lemma = self.get_separable_lemma(token)
                
                if language == 'thai':
                    if word == 'THAINEWSENTENCE':
                        thaiSentenceIndex = thaiSentenceIndex + 1
                        continue
                    else:
                        if word == 'NEWLINE':
                            thaiSentenceIndex = thaiSentenceIndex + 1

                        tokenizedWords.append(
                            {
                                "w": word,
                                "r": reading,
                                "l": lemma,
                                "lr": lemmaReading,
                                "pos": token.pos_,
                                "si": thaiSentenceIndex + sentenceIndexStart,
                                "g": gender,
                                "ip": token.is_punct,
                                "sb": space_before,
                                "sa": bool(token.whitespace_),
                            }
                        )
                else:
                    tokenizedWords.append(
                        {
                            "w": word,
                            "r": reading,
                            "l": lemma,
                            "lr": lemmaReading,
                            "pos": token.pos_,
                            "si": sentenceIndex + sentenceIndexStart,
                            "g": gender,
                            "ip": token.is_punct,
                            "sb": space_before,
                            "sa": bool(token.whitespace_),
                        }
                    )

        return tokenizedWords
    
    def get_separable_lemma(self, token):
        prefix = [c.text for c in token.children if c.dep_ == 'svp']
        if len(prefix) > 0:
            return prefix[0] + token.lemma_
        return token.lemma_