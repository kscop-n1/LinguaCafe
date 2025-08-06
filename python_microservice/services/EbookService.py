import lxml_html_clean
import lxml.html
from ebooklib import epub
import ebooklib
import re
from . import TokenizerConfig

config = TokenizerConfig.TokenizerConfig()


class EbookService:
    def loadEbookIntoChunks(
        self, importFile, language, chunkSize, textProcessingMethod, chapterSortMethod
    ):
        content = self.loadEbookFile(importFile, chapterSortMethod)
        content = content.replace('\r\n', ' NEWLINE ')
        content = content.replace('\n', ' NEWLINE ')

        # split text into sentences
        for sentenceEnding in config.sentenceEndings:
            content = content.replace(sentenceEnding, sentenceEnding + 'TMP_ST')
        sentences = content.split('TMP_ST')

        # split text into chunks
        chunks = list()
        for sentenceIndex, sentence in enumerate(sentences):
            if len(chunks) == 0 or len(chunks[-1].replace(' NEWLINE ', '')) > chunkSize:
                chunks.append('')

            chunks[-1] += sentences[sentenceIndex]
            chunks[-1] = chunks[-1].replace(' NEWLINE ', '\r\n')
            chunks[-1] = chunks[-1].replace('\xa0', ' ')

        return chunks

    def loadEbookFile(self, file, sortMethod):
        # rp and rt tags are used in adding prononciation over words, we need to remove the content of the tags
        cleaner = lxml_html_clean.Cleaner(
            allow_tags=[''],
            remove_unknown_tags=False,
            kill_tags=['rp', 'rt'],
            page_structure=False,
        )

        content = ''
        book = epub.read_epub(file)
        items = list(book.get_items())

        # ebooks have 2 ways to order chapters
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
