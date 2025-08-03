from youtube_transcript_api import YouTubeTranscriptApi
from youtube_transcript_api._errors import TranscriptsDisabled

class YoutubeService:
    def getYoutubeSubtitleList(self, videoId):
        youtubeApi = YouTubeTranscriptApi()
        
        try:
            subtitles = youtubeApi.list(videoId)
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
