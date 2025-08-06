from bottle import route, request, response, run, BaseRequest, HTTPResponse
BaseRequest.MEMFILE_MAX = 1024 * 1024 * 100
import os
import json
from urllib import parse
from newspaper import Article

from services import PackageManagerService
from services import YoutubeService
from services import SubtitleService
from services import TokenizerService
from services import EbookService

from pysubs2.exceptions import FormatAutodetectionError, Pysubs2Error

packageManagerService = PackageManagerService.PackageManagerService()
youtubeService = YoutubeService.YoutubeService()
subtitleService = SubtitleService.SubtitleService()
tokenizerService = TokenizerService.TokenizerService(packageManagerService)
ebookService = EbookService.EbookService()

# transform language names for spacy and stanza where they require a  
# different form than the human readable lower case one
def transformLanguage(language):
    if language == 'north sami':
        language = 'north_sami'

    return language

# responds to http requests from the main PHP site
@route('/tokenizer/tokenize-text', method='POST')
def tokenizeText():
    response.headers['Content-Type'] = 'application/json'

    # start = time.time()
    text = request.json.get('raw_text')
    language = transformLanguage(request.json.get('language'))
    tokenizer = request.json.get('tokenizer')

    return json.dumps(tokenizerService.tokenizePlainText(text, language, tokenizer))

@route('/tokenizer/tokenize-subtitles', method='POST')
def tokenizeSubtitles():
    response.headers['Content-Type'] = 'application/json'
    subtitles = json.loads(request.json.get('subtitles'))
    language = transformLanguage(request.json.get('language'))
    tokenizer = request.json.get('tokenizer')
    
    return json.dumps(tokenizerService.tokenizeSubtitles(subtitles, language, tokenizer))

# cuts the text given in post data into chunks
@route('/tokenizer/cut-and-tokenize-text', method='POST')
def cutAndTokenizeText():
    response.headers['Content-Type'] = 'application/json'
    text = request.json.get('text')
    language = transformLanguage(request.json.get('language'))
    chunkSize = request.json.get('chunkSize')
    
    return json.dumps(tokenizerService.cutAndTokenizePlainText(text, language, chunkSize))


# returns a raw text and a tokenized text 
# of n .epub file cut into chunks
@route('/tokenizer/import-book', method='POST')
def loadEbookIntoChunks():
    response.headers['Content-Type'] = 'application/json'
    
    importFile = request.json.get('importFile')
    language = transformLanguage(request.json.get('language'))
    chunkSize = request.json.get('chunkSize')
    textProcessingMethod = request.json.get('textProcessingMethod')
    chapterSortMethod = request.json.get('chapterSortMethod')
    
    bookTextChunks = ebookService.loadEbookIntoChunks(importFile, language,  chunkSize, textProcessingMethod, chapterSortMethod)

    return json.dumps(bookTextChunks)

# cuts the text given in post data into chunks
@route('/tokenizer/import-subtitles', method='POST')
def cutSubtitlesIntoChunks():
    response.headers['Content-Type'] = 'application/json'
    subtitles = json.loads(request.json.get('subtitles'))
    language = transformLanguage(request.json.get('language'))
    chunkSize = request.json.get('chunkSize')

    return json.dumps(tokenizerService.cutSubtitlesIntoChunks(subtitles, language, chunkSize))

@route('/youtube/get-subtitle-list', method='POST')
def getYoutubeSubtitleList():
    response.headers['Content-Type'] = 'application/json'

    url = request.json.get('url')
    parsedUrl = parse.urlparse(url)
    videoId = parse.parse_qs(parsedUrl.query)['v'][0]

    return json.dumps(youtubeService.getYoutubeSubtitleList(videoId))

@route('/subtitles/read', method='POST')
def getSubtitleContent():
    response.headers['Content-Type'] = 'application/json'
    fileName = request.json.get('fileName')

    try:
        content = subtitleService.getSubtitlesFileContent(fileName)
    except FormatAutodetectionError:
        return HTTPResponse(status=415, body=f"Error: the file {fileName} was not identified as a supported subtitle file.")
    except UnicodeDecodeError:
        return HTTPResponse(status=415, body=f"Error: this file {fileName} could not be decoded properly. Confirm the file is Unicode")
    except (IOError, Pysubs2Error):
        return HTTPResponse(status=415, body=f"Error: the file {fileName} could not be read.")

    return json.dumps(content)

@route('/web/get-website-text', method='POST')
def getWebsiteText():
    url = request.json.get('url')
    article = Article(url)
    article.download()
    article.parse()

    return json.dumps(article.text)

@route('/packages/uninstall-all', method = 'DELETE')
def remove_models():
    packageManagerService.delete_installed_packages()
    return HTTPResponse(status=200, body="Installed packages removed correctly")

@route('/packages/languages/install', method = 'POST')
def install_language_model():
    language = transformLanguage(request.json.get('language'))
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
