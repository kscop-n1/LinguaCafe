import type { UpdateNote } from '@lctypes/updates/UpdateNote'

const updateNotes: UpdateNote[] = [
    {
        title: 'v0.7',
        date: '2024-02-14',
        description:
            'This update replaced the Python Django server with Bottle for better performance and introduced dynamic language model loading to decrease memory usage. It also added several new import options and enhancements to the hover vocabulary box.',
        text: '',
        newFeatures: [
            'Replaced the python Django server with Bottle for better performance.',
            'Added dynamic language model loading. Models are only going to be loaded at the first import for each language. This will significantly decrease memory use based on which and how many langauges are used on a server after its startup.',
            "Added new 'Plain text' import option.",
            "Added new 'Text file' import option.",
            "Added new 'Jellyfin subtitle' import option.",
            "Added new 'Subtitle file' import option.",
            "Setting words' and phrases' level is now possible while hovering over them.",
            'Added dictionary search for hover vocabulary box.',
            'Added DeepL search for hover vocabulary box.',
            "Separated DeepL search from regular dictionary search in the vocabulary box. Users won't have to wait for DeepL server's response to see the dictionary search results which load much faster.",
            'Added loading indicator for vocabulary box dictionary search.',
            'Added an option to automatically highlight a word when it gets a translation added to it.',
            'A list of words which were not counted in the statistics now will be automatically set to ignored when imported. Also added all numbers and more symbols to the list.',
            "Vocabulary search page's edit dialog now will close on its own after saving it, and the search results will be updated automatically.",
            'Added chapter length option to import dialog.',
            'Added hotkeys for text scrolling.',
            'The selected library layout will now be remembered.',
            'Added option to change the default MySQL database and user.',
            'Added windows installation guide and installation file.',
        ],
        bugFixes: [
            'Fixed a bug that caused the records on the vocabulary search page to appear in random order inside the specified order by parameter.',
            'Fixed an issue in the text reader glossary, where empty parentheses were visible in languages that have no readings.',
            "Fixed a visual issue where the scrollbars' background were a different color than the area they were placed on.",
            'Fixed an issue that caused long chapter titles to hide the text.',
            'Furigana now displays the editable reading field. Previously it displayed the reading that was assigned to the exact word in text while importing it.',
            'Fixed a visual issue where the day text was not visible in the calendar while using dark theme.',
            'Added missing lemma to review card when using example sentence mode.',
            'Removed furigana from e-book imports.',
        ],
        otherChanges: [
            'Removed media player page, as it has been replaced with Jellyfin subtitle import option.',
            'Improved text selecting design.',
            'Removed unnecessary files from the webserver image and decreased its size.',
        ],
    },
    {
        title: 'v0.6',
        date: '2024-01-29',
        description:
            'This version introduced a fixed sidebar vocabulary for wider screens and a minimalistic hover vocabulary box. It also added YouTube subtitle import and hotkeys for the text reader and review pages.',
        text: '',
        newFeatures: [
            'Added an always open and fixed sidebar vocabulary as a default option for screens wider than 960px. It can be turned off in the text reader settings.',
            'Added a minimalistic vocabulary box, that appears when the user moves the mouse over a word. It can be turned off in the text reader settings.',
            'Added youtube subtitle import option.',
            'Added hotkeys for the text reader and review pages.',
            'Added proper finish reading screen.',
        ],
        bugFixes: [
            'Users clicking anywhere outside of the vocabulary box will close the vocabulary box. Previously it only worked if the user clicked on an empty space inside the text box.',
            'Increased maximum execution time for importing dictionaries. If your dictionary import did not end with a success message but still functional, I recommend importing it again, because it probably did not import all the records from the dictionary file.',
        ],
        otherChanges: ['Updated node.js to v20.11.0.'],
    },
    {
        title: 'v0.5.2',
        date: '2024-01-23',
        description:
            'This was a bug fix release that addressed a problem with deployment and included a simple migration guide.',
        text: '',
        newFeatures: [],
        bugFixes: ['Fixed a problem with deployment, and added a simple migration guide.'],
        otherChanges: [],
    },
    {
        title: 'v0.5.1',
        date: '2024-01-22',
        description:
            'This was a bug fix release that solved a problem which prevented lemmas and lemma readings from being saved.',
        text: '',
        newFeatures: [],
        bugFixes: ['Fixed a bug that prevented lemmas end lemma readings to be saved.'],
        otherChanges: [],
    },
    {
        title: 'v0.5',
        date: '2024-01-21',
        description:
            "Lemma modifications only apply to new words that haven't been imported yet. There will be an option in the future, to overwrite already existing lemmas.",
        text: `The docker installation process has been improved since the last version. Fixed all the known issues with it, and removed the requirement for users to create their folders manually and to modify the docker-compose.yml file.<br /><br /> There were several issues with Jellyfin integration. Now it should work properly with every language and video type. Also added information about subtitle file naming to the readme file.<br /><br /> Lemma modifications only apply to new words that haven't been imported yet. There will be an option in the future, to overwrite already existing lemmas.`,
        newFeatures: [
            'Added support for new languages: Czech and Welsh.',
            'Added Kengdic dictionary support for Korean.',
            'Added CC-CEDICT dictionary support for Chinese.',
            'Added Eurfa dictionary support for Welsh.',
            'Added pinyin support for Chinese.',
            'Added furigana support for Japanese.',
        ],
        bugFixes: [
            'Removed empty and duplicated records from dictionary search results.',
            'Added Chinese font type so every Chinese and Japanese text should be displayed with NotoSansSC and NotoSansJP font types.',
            'Removed word spacing from Chinese texts.',
            "Removed '+' symbol from Korean lemmas.",
            "Removed 'die/der/das' from the beginning of German search terms for better results.",
            'Fixed an issue with the Media player that only allowed TV Show subtitles to be displayed from Jellyfin; now every video type is playable.',
            'Fixed an issue with the Media player that caused every language to be handled as Japanese.',
            'Fixed an issue related to Media player missing language support; now all languages work with it.',
            'Fixed an issue on the vocabulary search page that caused phrases to be displayed with commas between the words, or with no spaces between them (further improvement is needed for non-Japanese and Chinese languages).',
            'Fixed an error that broke the Kanji page if you had no known Kanji yet from every category.',
            'Fixed dark theme style on color picker.',
            "Added a missing label to text reader's settings dialog.",
        ],
        otherChanges: [
            'Moved the dictionary search to the main section of the popup vocabulary.',
            'Removed all online dependencies.',
            'Updated language selection dialog design, and the supported languages information in the readme.',
            'Changed chapter size from 9000 to 4000 characters for importing e-books due to performance issues. There will be an option to edit this limit soon.',
            'Unified text reader and subtitle reader settings.',
            'Added dynamic subtitle rendering for Media player, which increased performance significantly. For this I had to remove customizable spacing between subtitles, due to a bug that moved the vocabulary box around. This will be fixed in the future.',
        ],
    },
    {
        title: 'v0.4',
        date: '2024-01-15',
        description:
            'German lemmatization was improved, and a new UI page was added to simplify and manage dictionary imports. The Docker installation and update process was also simplified significantly.',
        text: '',
        newFeatures: [
            'German lemmatization for nouns and separable verbs have been improved.',
            'Importing dictionaries have been simplified, and has a new UI page to manage them.',
            'Information popups for different features have been added.',
        ],
        bugFixes: [
            'Added missing toolbar titles on the review page.',
            'Added missing dialog close buttons.',
            'Auto generated lemmas were changed to only contain lowercase letters, instead of sometimes having uppercase letters.',
        ],
        otherChanges: [
            'Docker images are now published on the GitHub Container Registry, and there is a much simpler installation and update process.',
            'Changed version number format.',
            'Updated MySQL image to 8.0.',
        ],
    },
    {
        title: 'v0.3',
        date: '2024-01-12',
        description:
            'This update added partial support for Russian, Swedish, and Ukrainian. It also reworked the Docker installation process and split the "Highlight words" setting into two separate options for more control over highlighting.',
        text: '',
        newFeatures: [
            'Added partial support for new languages: Russian, Swedish and Ukrainian.',
            'Docker installation and update process has been reworked and made simpler.',
            'The "Highlight words" option in the text and subtitle reader settings dialog has been split into "Hide all highlighting" and "Hide new word highlighting" to make it possible to hide only the yellow new word highlighting.',
        ],
        bugFixes: [
            'Fixed all issues with "Plain text mode".',
            'Fixed a bug that caused words to only have top and bottom border without background when "Highlight words" option was turned off, and the user hovered over them.',
            'Missing toolbar titles have been added to text reader.',
            'A non-standard scroll function that caused issues in some browsers has been fixed and added as an option for text and subtitle reader.',
            'An issue has been fixed that caused white space characters being displayed as clickable words on the text reader page, and it broke the software if the user clicked on them.',
        ],
        otherChanges: [
            'The "/storage/app/dictionaries" folder has been created in the repository, and won\'t have to be created manually.',
            'All tokenizer models have been replaced with smaller sized ones due to possible performance issues. They will be replaced with larger models for specific languages if it causes word tagging accuracy issues.',
        ],
    },
    {
        title: 'v0.2',
        date: '2024-01-09',
        description:
            'This update added partial support for Chinese, Dutch, Finnish, French, Italian, and Korean. It also fixed a bug with the DeepL translator.',
        text: '',
        newFeatures: [
            'Added partial support for new languages: Chinese, Dutch, Finnish, French, Italian and Korean.',
        ],
        bugFixes: [
            'Fixed a bug that caused DeepL translator to handle all languages as Norwegian.',
        ],
        otherChanges: ['The selected language is displayed as capitalized text.'],
    },
    {
        title: 'v0.1',
        date: '2024-01-07',
        description: 'Initial release of the application.',
        text: 'Release.',
        newFeatures: [],
        bugFixes: [],
        otherChanges: [],
    },
]

export default updateNotes
