<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class VueMigrationStaticTest extends TestCase
{
    public function test_legacy_vuetify_overlay_and_select_patterns_are_not_used(): void
    {
        $files = $this->sourceFiles([
            base_path('resources/js'),
            base_path('resources/sass'),
        ]);

        $legacyPatterns = [
            'legacy v-menu offset-y prop' => '/\boffset-y\b/',
            'legacy v-menu nudge positioning prop' => '/\bnudge-(top|bottom|left|right)=/',
            'legacy absolute menu x positioning prop' => '/\bposition-x\b/',
            'legacy absolute menu y positioning prop' => '/\bposition-y\b/',
            'legacy v-select item-text prop' => '/\bitem-text=/',
            'obsolete menu-button workaround' => '/\bmenu-button\b/',
            'legacy Vue 2 dialog input emit declaration' => '/emits:\s*\[\'input\'\]/',
            'malformed Vue 3 model listener' => '/@input:model-value=/',
            'legacy Vuetify 2 theme css variable' => '/var\(--v-[A-Za-z0-9]+-base\)/',
            'legacy Vuetify 2 input slot selector' => '/\.v-input__slot\b/',
            'legacy Vuetify 2 text field state selector' => '/\.v-input--(has-state|is-disabled)\b/',
            'legacy Vuetify 2 background-color component prop' => '/\bbackground-color=/',
            'legacy Vuetify 2 text color utility' => '/\b(text|white|error|success)--text\b/',
            'legacy Vuetify 2 mini drawer selector' => '/\.v-navigation-drawer--mini-variant\b/',
            'invalid Vue 3 model change listener' => '/@change:model-value=/',
            'legacy Vuetify 2 dark theme prop' => '/<[A-Za-z][^>]*\s:?dark=/',
            'legacy forced dark local theme' => '/<[A-Za-z][^>]*\stheme="dark"/',
            'legacy Vuetify 2 text button prop' => '/<v-btn[^>]*\stext(\s|>)/',
        ];

        $failures = [];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            foreach ($legacyPatterns as $description => $pattern) {
                if (preg_match($pattern, $contents) === 1) {
                    $failures[] = $description . ' in ' . str_replace(base_path() . '/', '', $file);
                }
            }
        }

        $this->assertSame([], $failures);
    }

    public function test_varela_font_is_a_vite_managed_source_asset(): void
    {
        $this->assertFileExists(base_path('resources/fonts/VarelaRound-Regular.ttf'));
        $this->assertFileDoesNotExist(base_path('public/fonts/VarelaRound-Regular.ttf'));

        $appScss = file_get_contents(base_path('resources/sass/app.scss'));

        $this->assertStringContainsString(
            "url('../fonts/VarelaRound-Regular.ttf')",
            $appScss,
            'The Varela font must be referenced as a relative source asset so Vite fingerprints it.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/url\(["\']?\/fonts\/VarelaRound-Regular\.ttf/',
            $appScss,
            'The bundled Varela font must not use a public runtime /fonts URL.'
        );
    }

    public function test_bootstrap_sass_and_legacy_frontend_dependencies_are_removed(): void
    {
        $sassFiles = $this->sourceFiles([base_path('resources/sass')]);
        $failures = [];

        foreach ($sassFiles as $file) {
            $contents = file_get_contents($file);

            foreach ([
                'deprecated Sass import' => '/^\s*@import\s+/m',
                'Bootstrap Sass import' => '/bootstrap\/scss/',
                'deprecated Bootstrap color function' => '/\b(lighten|darken)\s*\(/',
                'deprecated Bootstrap global map merge' => '/\bmap-merge\s*\(/',
            ] as $description => $pattern) {
                if (preg_match($pattern, $contents) === 1) {
                    $failures[] = $description . ' in ' . str_replace(base_path() . '/', '', $file);
                }
            }
        }

        $packageJson = json_decode(file_get_contents(base_path('package.json')), true);
        $dependencies = array_merge($packageJson['dependencies'] ?? [], $packageJson['devDependencies'] ?? []);

        foreach (['bootstrap', 'jquery', 'popper.js', '@popperjs/core'] as $package) {
            if (array_key_exists($package, $dependencies)) {
                $failures[] = 'legacy frontend package ' . $package . ' is still installed';
            }
        }

        $viteConfig = file_get_contents(base_path('vite.config.mjs'));

        if (strpos($viteConfig, 'node_modules/bootstrap') !== false || strpos($viteConfig, '~bootstrap') !== false) {
            $failures[] = 'legacy Bootstrap Vite alias is still configured';
        }

        $this->assertSame([], $failures);
    }

    public function test_vuetify_activator_slots_bind_props_to_their_anchor(): void
    {
        $failures = [];

        foreach ($this->sourceFiles([base_path('resources/js/components')]) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'vue') {
                continue;
            }

            $lines = file($file, FILE_IGNORE_NEW_LINES);

            foreach ($lines as $index => $line) {
                if (strpos($line, 'v-slot:activator="{ props }"') === false) {
                    continue;
                }

                $block = implode("\n", array_slice($lines, $index + 1, 8));

                if (strpos($block, 'v-bind="props"') === false) {
                    $failures[] = str_replace(base_path() . '/', '', $file) . ':' . ($index + 1);
                }
            }
        }

        $this->assertSame([], $failures);
    }

    public function test_vuetify_3_server_tables_use_current_pagination_props(): void
    {
        $failures = [];

        foreach ($this->sourceFiles([base_path('resources/js/components')]) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'vue') {
                continue;
            }

            $contents = file_get_contents($file);
            $relativePath = str_replace(base_path() . '/', '', $file);

            if (strpos($contents, 'server-items-length') !== false) {
                $failures[] = 'legacy server-items-length prop in ' . $relativePath;
            }

            if (strpos($contents, 'footer-props') !== false && strpos($contents, 'v-data-table') !== false) {
                $failures[] = 'legacy data-table footer-props in ' . $relativePath;
            }
        }

        $this->assertSame([], $failures);
    }

    public function test_chapter_table_footer_menus_use_custom_activator_positioning(): void
    {
        $files = [
            'resources/js/components/Library/BookChapters.vue',
            'resources/js/components/TextReader/TextReaderChapterList.vue',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents(base_path($file));

            $this->assertMatchesRegularExpression(
                '/tableFooterDefaults:\s*\{.*?VSelect:\s*\{.*?menuProps:\s*\{.*?locationStrategy:\s*this\.positionFooterSelectMenu,.*?scrollStrategy:\s*["\']reposition["\']/s',
                $contents,
                $file . ' must position footer select menus from the current activator rect so Vuetify overlays do not drift after document scroll.'
            );
            $this->assertStringContainsString('target.getBoundingClientRect()', $contents);
            $this->assertStringContainsString('position: "fixed"', $contents);
            $this->assertStringContainsString('document.addEventListener("scroll", updateLocation, true)', $contents);
        }
    }

    public function test_vocabulary_and_chapter_requests_preserve_latest_server_state(): void
    {
        $vocabulary = file_get_contents(base_path('resources/js/components/Vocabulary/Vocabulary.vue'));
        $chapters = file_get_contents(base_path('resources/js/components/Library/BookChapters.vue'));

        $this->assertStringContainsString('vocabularyRequestSequence: 0', $vocabulary);
        $this->assertStringContainsString('const requestSequence = ++this.vocabularyRequestSequence', $vocabulary);
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($vocabulary, 'requestSequence !== this.vocabularyRequestSequence'),
            'Vocabulary success and error callbacks must both ignore stale requests.'
        );
        $this->assertMatchesRegularExpression(
            '/loadVocabularySearchPage\\(\\)\\s*\\{.*?this\\.loading = true;.*?axios\\.post/s',
            $vocabulary
        );

        $this->assertStringContainsString('const requestSequence = ++this.chapterRequestSequence', $chapters);
        $this->assertGreaterThanOrEqual(2, substr_count($chapters, 'requestSequence !== this.chapterRequestSequence'));
        $this->assertStringContainsString('requestData.all = true', $chapters);
        $this->assertStringContainsString('requestData.perPage = itemsPerPage', $chapters);
        $this->assertStringContainsString('this.totalChapters = Number(response.data.total', $chapters);
        $this->assertStringContainsString(
            "{{ chaptersError ? 'Unable to load chapters.' : 'No data available' }}",
            $chapters
        );
    }

    /**
     * @param array<int, string> $directories
     * @return array<int, string>
     */
    public function test_regression_ui_patterns_are_preserved(): void
    {
        foreach ([
            "resources/js/components/Admin/AdminUserSettings.vue",
            "resources/js/components/Admin/AdminDictionarySettings.vue",
            "resources/js/components/Admin/AdminFontTypeSettings.vue",
            "resources/js/components/Vocabulary/Vocabulary.vue",
        ] as $file) {
            $contents = file_get_contents(base_path($file));

            $this->assertStringContainsString("table-action-button", $contents, $file . " must use compact table action buttons.");
        }

        $appScss = file_get_contents(base_path("resources/sass/app.scss"));
        $this->assertStringContainsString(".table-action-button.v-btn", $appScss);
        $this->assertStringContainsString(".app-dialog-card", $appScss);
        $this->assertStringContainsString(".vertical-toolbar-button.v-btn", $appScss);

        $this->assertStringContainsString("vertical-toolbar-button", file_get_contents(base_path("resources/js/components/Review/Review.vue")));
        $this->assertStringContainsString("vertical-toolbar-button", file_get_contents(base_path("resources/js/components/TextReader/TextReader.vue")));
    }

    public function test_vocabulary_import_manual_link_targets_existing_markdown_section(): void
    {
        $dialog = file_get_contents(base_path("resources/js/components/Vocabulary/VocabularyImportDialog.vue"));
        $manual = file_get_contents(base_path("manual/Setup.md"));

        $this->assertStringContainsString("/user-manual/Setup#Importing%20Vocabulary%20into%20LinguaCafe", $dialog);
        $this->assertStringContainsString("# Importing Vocabulary into LinguaCafe", $manual);
    }

    public function test_fixed_height_dialog_regressions_are_not_reintroduced(): void
    {
        foreach ([
            "resources/js/components/Admin/AdminEditUserDialog.vue",
            "resources/js/components/Admin/AdminEditFontTypeDialog.vue",
            "resources/js/components/TextReader/TextReaderSettings.vue",
        ] as $file) {
            $contents = file_get_contents(base_path($file));

            $this->assertStringContainsString("app-dialog-card", $contents, $file . " must use viewport-aware dialog card sizing.");
            $this->assertStringNotContainsString("height=\"300px\"", $contents);
            $this->assertStringNotContainsString("style=\"height: 800px;\"", $contents);
        }
    }

    /**
     *  array<int, string> $directories
     *  array<int, string>
     */
    private function sourceFiles(array $directories): array
    {
        $files = [];

        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                if (!in_array($file->getExtension(), ['js', 'scss', 'vue'], true)) {
                    continue;
                }

                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
