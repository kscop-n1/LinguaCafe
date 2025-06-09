from bottle import route, request, response, run, BaseRequest, HTTPResponse
BaseRequest.MEMFILE_MAX = 1024 * 1024 * 100
import sys
import os
import json
import time
import re
import ebooklib 
import html
import pinyin
from ebooklib import epub
from urllib import parse
import lxml_html_clean
import lxml.html
from newspaper import Article

import PackageManagerService
import YoutubeService
import TokenizerService

packageManagerService = PackageManagerService.PackageManagerService()
youtubeService = YoutubeService.YoutubeService()
tokenizerService = TokenizerService.TokenizerService(packageManagerService)

# used for german separable verbs
def get_separable_lemma(token):
    prefix = [c.text for c in token.children if c.dep_ == 'svp']
    if len(prefix) > 0:
        return prefix[0] + token.lemma_
    return token.lemma_

# loads an .epub file
def loadBook(file, sortMethod):
    # rp and rt tags are used in adding prononciation over words, we need to remove the content of the tags
    cleaner = lxml_html_clean.Cleaner(allow_tags=[''], remove_unknown_tags=False, kill_tags = ['rp','rt'], page_structure=False)
    content = ''
    book = epub.read_epub(file)
    items = list(book.get_items())

    # select sorting method for chapters
    if sortMethod == 'spine':
        sortedItems = list()
        for item in enumerate(book.spine):
            sortedItems.append(book.get_item_with_id(item[1][0]))
    else:
        sortedItems = items

    for item in sortedItems:
        if item.get_type() == ebooklib.ITEM_DOCUMENT:
            # clean_html cannot be passed bytes but it cannot be passed a str
            # with explicit encoding either. So you must convert it to a string
            # and then use RegEx to remove the encoding declaration
            content_str = item.get_content().decode()
            content_str = re.sub(r'<\?xml[^>]+\?>', '', content_str, count=1)
            epubPage = cleaner.clean_html(content_str)
            # needed to removed extra div created by cleaner...
            epubPage = lxml.html.fromstring(epubPage).text_content()
            content += epubPage
    return str(content)

# responds to http requests from the main PHP site
@route('/tokenizer/tokenize-text', method='POST')
def tokenizeText():
    response.headers['Content-Type'] = 'application/json'

    # start = time.time()
    text = request.json.get('raw_text')
    language = request.json.get('language')
    tokenizer = request.json.get('tokenizer')

    return json.dumps(tokenizerService.tokenizePlainText(text, language, tokenizer))

@route('/tokenizer/tokenize-subtitles', method='POST')
def tokenizeSubtitles():
    response.headers['Content-Type'] = 'application/json'
    subtitles = json.loads(request.json.get('subtitles'))
    language = request.json.get('language')
    tokenizer = request.json.get('tokenizer')
    
    return json.dumps(tokenizerService.tokenizeSubtitles(subtitles, language, tokenizer))

# cuts the text given in post data into chunks
@route('/tokenizer/cut-and-tokenize-text', method='POST')
def cutAndTokenizeText():
    response.headers['Content-Type'] = 'application/json'
    text = request.json.get('text')
    language = request.json.get('language')
    chunkSize = request.json.get('chunkSize')
    
    return json.dumps(tokenizerService.cutAndTokenizePlainText(text, language, chunkSize))


# returns a raw text and a tokenized text 
# of n .epub file cut into chunks
@route('/tokenizer/import-book', method='POST')
def importBook():
    response.headers['Content-Type'] = 'application/json'
    chunkSize = request.json.get('chunkSize')
    textProcessingMethod = request.json.get('textProcessingMethod')
    importFile = request.json.get('importFile')
    language = request.json.get('language')
    chapterSortMethod = request.json.get('chapterSortMethod')
    
    # load book
    content = loadBook(importFile, chapterSortMethod)
    content = content.replace('\r\n', ' NEWLINE ')
    content = content.replace('\n', ' NEWLINE ')

    # split text into sentences
    for sentenceEnding in sentenceEndings:
        content = content.replace(sentenceEnding, sentenceEnding + 'TMP_ST')
    sentences = content.split('TMP_ST')

    # split book into chunks
    chunks = list()
    for sentenceIndex, sentence in enumerate(sentences):
        if (len(chunks) == 0 or len(chunks[-1].replace(' NEWLINE ', '')) > chunkSize):
            chunks.append('')

        chunks[-1] += sentences[sentenceIndex]
        chunks[-1] = chunks[-1].replace(' NEWLINE ', '\r\n')
        chunks[-1] = chunks[-1].replace('\xa0', ' ')

    return json.dumps(chunks)

# cuts the text given in post data into chunks
@route('/tokenizer/import-subtitles', method='POST')
def cutSubtitlesIntoChunks():
    response.headers['Content-Type'] = 'application/json'
    subtitles = json.loads(request.json.get('subtitles'))
    language = request.json.get('language')
    chunkSize = request.json.get('chunkSize')

    return json.dumps(tokenizerService.cutSubtitlesIntoChunks(subtitles, language, chunkSize))

@route('/tokenizer/get-youtube-subtitle-list', method='POST')
def getYoutubeSubtitleList():
    response.headers['Content-Type'] = 'application/json'

    url = request.json.get('url')
    parsedUrl = parse.urlparse(url)
    videoId = parse.parse_qs(parsedUrl.query)['v'][0]

    return json.dumps(youtubeService.getYoutubeSubtitleList(videoId))

@route('/tokenizer/get-subtitle-file-content', method='POST')
def getYoutubeSubtitleContent():
    response.headers['Content-Type'] = 'application/json'
    
    fileName = request.json.get('fileName')

    return json.dumps(youtubeService.getYoutubeSubtitleContent(fileName))

@route('/tokenizer/get-website-text', method='POST')
def getWebsiteText():
    url = request.json.get('url')
    article = Article(url)
    article.download()
    article.parse()

    return json.dumps(article.text);

@route('/packages/uninstall-all', method = 'DELETE')
def remove_models():
    packageManagerService.delete_installed_packages()
    return PlainTextResponse(content="Installed packages removed correctly")

@route('/packages/languages/install', method = 'POST')
def install_language_model():
    language = request.json.get('language')
    tokenizer = request.json.get('tokenizer')
    if tokenizer is None or language is None:
        return HTTPResponse(status=422, body="Error: missing parameter")

    if packageManagerService.install_language_model(language, tokenizer):
        return HTTPResponse(status=200, body="Language and dependencies installed correctly")
    else:
        return HTTPResponse(status=500, body=f"Error: {e}")

@route('/packages/list', method = 'GET')
def list_installed_packages():
    return packageManagerService.list_installed_packages()

if 'APP_ENV' in os.environ and os.environ['APP_ENV'] == 'production':
    print('Production server started')
    run(host='0.0.0.0', port=8678, reloader=False, debug=False)
else:
    print('Development server started')
    run(host='0.0.0.0', port=8678, reloader=True, debug=True)
