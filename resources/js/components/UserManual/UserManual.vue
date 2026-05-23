<template>
    <div id="user-manual">
        <!-- Menu -->
        <div id="user-manual-menu">
            <v-card outlined class="rounded-lg ma-4 pa-2 pl-0">
                <v-treeview
                    v-if="pages"
                    shaped
                    dense
                    hoverable
                    activatable
                    open-on-click
                    return-object
                    open-all
                    color="primary"
                    :items="pages"
                    @update:active="updateSelectedPage"
                >
                    <template #label="{ item }">
                        <span>{{item.name}}</span>
                    </template>
                </v-treeview>
            </v-card>
        </div>

        <!-- Pages -->
        <div id="user-manual-content">
            <div v-if="userManualFile" class="user-manual-markdown" v-html="renderedManual"></div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

    export default {
        data: function() {
            return {
                userManualFile: null,
                selectedPage: 'Home',
                pages: null
            }
        },
        computed: {
            renderedManual() {
                if (!this.userManualFile) {
                    return '';
                }

                return this.renderMarkdown(this.userManualFile);
            }
        },
        mounted() {
            axios.get('/manual/get-menu-tree').then((response) => {
                this.pages = response.data;
            });
            
            
            if (this.$route.params.currentPage !== undefined) {
                this.selectedPage = this.$route.params.currentPage;
            }
            
            this.loadManualFile(this.selectedPage);
        },
        props: {
        },
        methods: {
            replaceElements(dom) {
                // admonitions
                dom = dom.replaceAll('[!NOTE]', '<admonition class="note"><i aria-hidden="true" class="v-icon notranslate mdi mdi-information-outline"></i> <span>Note</span></admonition>');
                dom = dom.replaceAll('[!TIP]', '<admonition class="tip"><i aria-hidden="true" class="v-icon notranslate mdi mdi-lightbulb-outline"></i> <span>Tip</span></admonition>');
                dom = dom.replaceAll('[!IMPORTANT]', '<admonition class="important-note"><i aria-hidden="true" class="v-icon notranslate mdi mdi-message-alert-outline"></i> <span>Important</span></admonition>');
                dom = dom.replaceAll('[!WARNING]', '<admonition class="warning"><i aria-hidden="true" class="v-icon notranslate mdi mdi-alert-outline"></i> <span>Warning</span></admonition>');
                dom = dom.replaceAll('[!CAUTION]', '<admonition class="caution"><i aria-hidden="true" class="v-icon notranslate mdi mdi-alert-circle-outline"></i> <span>Caution</span></admonition>');

                // flag images
                dom = dom.replaceAll('images/flags/', '/images/flags/');
                return dom;
            },
            updateSelectedPage(event) {
                if (!event.length) {
                    return;
                }
                
                var hash = '';
                if (window.location.hash) {
                    hash = window.location.hash;
                }

                var currentPath = '' + this.$router.currentRoute.path + hash;
                var newPath = '' + '/user-manual/' + event[0].fileName;
                if (currentPath !== newPath) {
                    this.$router.push({ path: '/user-manual/' + event[0].fileName, replace: true });
                }
                
            },
            loadManualFile(fileName) {
                this.userManualFile = null;
                axios.get('/manual/get-manual-file/' + fileName).then((response) => {
                    this.userManualFile = this.replaceElements(response.data);
                    
                    
                    if (window.location.hash) {
                        var hash = window.location.hash;
                        hash = decodeURI(hash);
                        hash = hash.toLowerCase().replaceAll(' ', '-').replaceAll('?', '').replaceAll(',', '').replaceAll('.', '');

                        this.$nextTick(() => {
                            var target = document.querySelector(hash);
                            if (target) {
                                target.scrollIntoView(hash);
                            }
                        });
                    }
                });
            },
            renderMarkdown(markdown) {
                var lines = markdown.replace(/\r\n/g, '\n').split('\n');
                var html = [];
                var paragraph = [];
                var listType = null;
                var inCodeBlock = false;
                var codeLines = [];
                var inBlockquote = false;
                var blockquoteLines = [];
                var inDetails = false;

                var flushParagraph = () => {
                    if (paragraph.length) {
                        html.push('<p>' + paragraph.map(this.renderInlineMarkdown).join(' ') + '</p>');
                        paragraph = [];
                    }
                };

                var flushList = () => {
                    if (listType) {
                        html.push('</' + listType + '>');
                        listType = null;
                    }
                };

                var flushBlockquote = () => {
                    if (inBlockquote) {
                        html.push('<blockquote>' + blockquoteLines.map(this.renderInlineMarkdown).join('<br>') + '</blockquote>');
                        blockquoteLines = [];
                        inBlockquote = false;
                    }
                };

                var renderTable = (startIndex) => {
                    var rows = [];
                    var index = startIndex;
                    while (index < lines.length && /^\s*\|.*\|\s*$/.test(lines[index])) {
                        rows.push(lines[index]);
                        index++;
                    }

                    if (rows.length < 2 || !/^\s*\|?\s*:?-{3,}:?/.test(rows[1])) {
                        return null;
                    }

                    var headerCells = this.splitTableRow(rows[0]);
                    var bodyRows = rows.slice(2).map((row) => this.splitTableRow(row));
                    var table = '<table><thead><tr>' + headerCells.map((cell) => '<th>' + this.renderInlineMarkdown(cell) + '</th>').join('') + '</tr></thead><tbody>';
                    bodyRows.forEach((row) => {
                        table += '<tr>' + row.map((cell) => '<td>' + this.renderInlineMarkdown(cell) + '</td>').join('') + '</tr>';
                    });
                    table += '</tbody></table>';

                    return {
                        html: table,
                        nextIndex: index
                    };
                };

                for (var i = 0; i < lines.length; i++) {
                    var line = lines[i];

                    if (/^```/.test(line)) {
                        flushParagraph();
                        flushList();
                        flushBlockquote();

                        if (inCodeBlock) {
                            html.push('<pre><code>' + this.escapeHtml(codeLines.join('\n')) + '</code></pre>');
                            codeLines = [];
                            inCodeBlock = false;
                        } else {
                            inCodeBlock = true;
                        }
                        continue;
                    }

                    if (inCodeBlock) {
                        codeLines.push(line);
                        continue;
                    }

                    if (/^\s*$/.test(line)) {
                        flushParagraph();
                        flushList();
                        flushBlockquote();
                        continue;
                    }

                    if (/^\s*\|.*\|\s*$/.test(line)) {
                        flushParagraph();
                        flushList();
                        flushBlockquote();
                        var table = renderTable(i);
                        if (table) {
                            html.push(table.html);
                            i = table.nextIndex - 1;
                            continue;
                        }
                    }

                    if (/^<details>\s*$/.test(line)) {
                        flushParagraph();
                        flushList();
                        flushBlockquote();
                        html.push('<details>');
                        inDetails = true;
                        continue;
                    }

                    if (/^<\/details>\s*$/.test(line)) {
                        flushParagraph();
                        flushList();
                        flushBlockquote();
                        html.push('</details>');
                        inDetails = false;
                        continue;
                    }

                    var summaryMatch = line.match(/^<summary>(.*)<\/summary>\s*$/);
                    if (summaryMatch) {
                        flushParagraph();
                        flushList();
                        flushBlockquote();
                        html.push('<summary>' + this.renderInlineMarkdown(summaryMatch[1]) + '</summary>');
                        continue;
                    }

                    var heading = line.match(/^(#{1,6})\s+(.+)$/);
                    if (heading) {
                        flushParagraph();
                        flushList();
                        flushBlockquote();
                        var level = heading[1].length;
                        var text = heading[2].trim();
                        html.push('<h' + level + ' id="' + this.headingId(text) + '">' + this.renderInlineMarkdown(text) + '</h' + level + '>');
                        continue;
                    }

                    var quote = line.match(/^>\s?(.*)$/);
                    if (quote) {
                        flushParagraph();
                        flushList();
                        inBlockquote = true;
                        blockquoteLines.push(quote[1]);
                        continue;
                    }

                    var unordered = line.match(/^\s*[-*]\s+(.+)$/);
                    if (unordered) {
                        flushParagraph();
                        flushBlockquote();
                        if (listType !== 'ul') {
                            flushList();
                            html.push('<ul>');
                            listType = 'ul';
                        }
                        html.push('<li>' + this.renderInlineMarkdown(unordered[1]) + '</li>');
                        continue;
                    }

                    var ordered = line.match(/^\s*\d+\.\s+(.+)$/);
                    if (ordered) {
                        flushParagraph();
                        flushBlockquote();
                        if (listType !== 'ol') {
                            flushList();
                            html.push('<ol>');
                            listType = 'ol';
                        }
                        html.push('<li>' + this.renderInlineMarkdown(ordered[1]) + '</li>');
                        continue;
                    }

                    flushList();
                    flushBlockquote();
                    paragraph.push(line);
                }

                flushParagraph();
                flushList();
                flushBlockquote();

                if (inCodeBlock) {
                    html.push('<pre><code>' + this.escapeHtml(codeLines.join('\n')) + '</code></pre>');
                }

                if (inDetails) {
                    html.push('</details>');
                }

                return html.join('\n');
            },
            splitTableRow(row) {
                return row.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map((cell) => cell.trim());
            },
            renderInlineMarkdown(text) {
                var placeholders = [];
                var store = (html) => {
                    placeholders.push(html);
                    return '\u0000' + (placeholders.length - 1) + '\u0000';
                };

                text = text.replace(/<kbd>(.*?)<\/kbd>/g, (match, label) => store('<kbd>' + this.escapeHtml(label) + '</kbd>'));
                text = text.replace(/<b>(.*?)<\/b>/g, (match, label) => store('<b>' + this.escapeHtml(label) + '</b>'));
                text = text.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, (match, label, url) => store('<a href="' + this.escapeAttribute(url) + '" target="_blank" rel="noopener noreferrer">' + this.renderInlineMarkdown(label) + '</a>'));
                text = text.replace(/`([^`]+)`/g, (match, code) => store('<code>' + this.escapeHtml(code) + '</code>'));

                var escaped = this.escapeHtml(text);
                escaped = escaped.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
                escaped = escaped.replace(/__([^_]+)__/g, '<strong>$1</strong>');
                escaped = escaped.replace(/\u0000(\d+)\u0000/g, (match, index) => placeholders[Number(index)] || '');

                return escaped;
            },
            headingId(text) {
                return text.toLowerCase().replaceAll(' ', '-').replaceAll('?', '').replaceAll(',', '').replaceAll('.', '');
            },
            escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            },
            escapeAttribute(value) {
                return this.escapeHtml(value).replace(/`/g, '&#096;');
            }
        }
    }
</script>
 
