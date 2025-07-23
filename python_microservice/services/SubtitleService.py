import pysubs2
from pysubs2.time import ms_to_str

class SubtitleService:
    def getSubtitlesFileContent(self, fileName):
        subtitles = pysubs2.load(fileName)

        return [
            {
                'text': s.plaintext,
                'start': ms_to_str(s.start),
                'end': ms_to_str(s.end),
            } for s in subtitles
        ]