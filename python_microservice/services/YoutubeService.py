import json
from youtube_transcript_api import YouTubeTranscriptApi
from youtube_transcript_api._errors import TranscriptsDisabled
from pysubparser import parser
from pysubparser.cleaners import formatting

class YoutubeService:
    def getYoutubeSubtitleList(self, videoId):
        ytt_api = YouTubeTranscriptApi()
        try:
            subtitles = YouTubeTranscriptApi.list_transcripts(videoId)
        except TranscriptsDisabled: 
            return list()
        

        subtitleList = list()
        for subtitle in subtitles:
            
            subtitleList.append({
                'language': subtitle.language, 
                'languageLowerCase': subtitle.language.lower(), 
                'languageCode': subtitle.language_code, 
                'text': '\n'.join(line.text for line in subtitle.fetch())
            })

        return subtitleList

    def getYoutubeSubtitleContent(self, fileName):
        subtitleContent = list()
        
        subtitles = parser.parse(fileName)
        subtitles = formatting.clean(subtitles)
        for subtitle in subtitles:
            start = str(subtitle.start).split('.')[0]
            end = str(subtitle.end).split('.')[0]
            subtitleContent.append({
                'text': str(subtitle.text),
                'start': start,
                'end': end
            })

        return subtitleContent