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
            "resources/js/components/Library/BookChapters.vue",
            "resources/js/components/Library/BookListLayout/BookListTable.vue",
        ] as $file) {
            $contents = file_get_contents(base_path($file));

            $this->assertStringContainsString("table-action-button", $contents, $file . " must use compact table action buttons.");
        }

        $appScss = file_get_contents(base_path("resources/sass/app.scss"));
        $this->assertMatchesRegularExpression(
            '/\.table-action-button\.v-btn\s*\{[^}]*width:\s*32px\s*!important;[^}]*height:\s*32px\s*!important;[^}]*flex:\s*0 0 32px;[^}]*aspect-ratio:\s*1;/s',
            $appScss
        );
        $this->assertMatchesRegularExpression(
            '/\.vertical-toolbar-button\.v-btn\s*\{[^}]*width:\s*40px\s*!important;[^}]*height:\s*40px\s*!important;[^}]*flex:\s*0 0 40px;[^}]*aspect-ratio:\s*1;/s',
            $appScss
        );
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($appScss, ':not(.table-action-button):not(.vertical-toolbar-button)'),
            'Global desktop and mobile text-button rules must not override shared icon-button geometry.'
        );

        $this->assertStringContainsString("vertical-toolbar-button", file_get_contents(base_path("resources/js/components/Review/Review.vue")));
        $this->assertStringContainsString("vertical-toolbar-button", file_get_contents(base_path("resources/js/components/TextReader/TextReader.vue")));

        foreach ([
            "resources/js/components/Admin/AdminUserSettings.vue",
            "resources/js/components/Admin/AdminDictionarySettings.vue",
            "resources/js/components/Admin/AdminFontTypeSettings.vue",
            "resources/js/components/Library/BookChapters.vue",
            "resources/js/components/Library/BookListLayout/BookListTable.vue",
        ] as $file) {
            $this->assertMatchesRegularExpression(
                '/title:\s*[\'"]Actions[\'"].*?width:\s*[\'"]96px[\'"]/s',
                file_get_contents(base_path($file)),
                $file . ' must reserve the shared compact action-column width.'
            );
        }
    }

    public function test_sidebar_bottom_controls_share_one_vuetify_3_alignment_contract(): void
    {
        $layout = file_get_contents(base_path("resources/js/components/Layout.vue"));
        $styles = file_get_contents(base_path("resources/sass/app.scss"));

        $this->assertSame(3, substr_count($layout, 'navigation-button navigation-bottom-item'));
        $this->assertStringContainsString(
            'class="navigation-button navigation-bottom-item navigation-language-button"',
            $layout
        );
        $this->assertMatchesRegularExpression(
            '/navigation-language-button[\s\S]*?<template #prepend>[\s\S]*?<v-img class="navigation-flag border"/',
            $layout
        );

        $this->assertMatchesRegularExpression(
            '/\.navigation-bottom-item\s*\{[^}]*height:\s*40px;[^}]*min-height:\s*40px;[^}]*padding-block:\s*0px;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.navigation-bottom-item \.v-list-item__prepend\s*\{[^}]*width:\s*40px;[^}]*align-items:\s*center;[^}]*justify-content:\s*flex-start;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.navigation-bottom-item \.v-list-item-title\s*\{[^}]*padding-left:\s*0px;[^}]*line-height:\s*20px;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-navigation-drawer__append\s*\{[^}]*padding-bottom:\s*calc\(12px \+ env\(safe-area-inset-bottom,\s*0px\)\);/s',
            $styles
        );
        $this->assertStringNotContainsString('margin-left: 1px', $styles);
        $this->assertStringNotContainsString('margin-right: -1px', $styles);
        $this->assertStringNotContainsString('.navigation-bottom-title', $styles);
    }

    public function test_vocabulary_import_manual_link_targets_existing_markdown_section(): void
    {
        $dialog = file_get_contents(base_path("resources/js/components/Vocabulary/VocabularyImportDialog.vue"));
        $manual = file_get_contents(base_path("manual/Setup.md"));
        $manualComponent = file_get_contents(base_path("resources/js/components/UserManual/UserManual.vue"));
        $webRoutes = file_get_contents(base_path("routes/web.php"));

        $this->assertStringContainsString(
            'href="/user-manual/Setup#Importing%20Vocabulary%20into%20LinguaCafe"',
            $dialog
        );
        $this->assertStringContainsString(
            "Route::get('/user-manual/{currentPage?}'",
            $webRoutes
        );
        $this->assertStringContainsString("# Importing Vocabulary into LinguaCafe", $manual);
        $this->assertStringContainsString("The CSV file can have these columns", $manual);
        $this->assertStringContainsString('id="\' + this.headingId(text) + \'"', $manualComponent);
        $this->assertStringContainsString("normalizedFileName.split('#')[0]", $manualComponent);
        $this->assertStringNotContainsString('href="/user-manual/vocabulary-import"', $dialog);
    }

    public function test_home_mobile_goal_cards_shrink_to_the_available_grid_track(): void
    {
        $styles = file_get_contents(base_path("resources/sass/Home/Home.scss"));

        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 575px\)[\s\S]*?#goals[\s\S]*?\.goal\s*\{[^}]*width:\s*100%;[^}]*max-width:\s*360px;[^}]*justify-self:\s*center;[^}]*margin-inline:\s*auto\s*!important;/s',
            $styles
        );
        $this->assertStringNotContainsString(
            ".goal {\n            width: 360px;",
            $styles
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\.goal\s*\{[^}]*margin-left:\s*8px\s*!important;[^}]*margin-right:\s*8px\s*!important;/s',
            $styles
        );
    }

    public function test_dark_forms_cards_and_menus_use_current_semantic_state_selectors(): void
    {
        $styles = file_get_contents(base_path("resources/sass/DarkMode.scss"));

        $this->assertStringContainsString(
            "#app .v-application.dark,\n.v-overlay-container .v-theme--dark {",
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-alert a\s*\{[^}]*color:\s*inherit\s*!important;[^}]*text-decoration:\s*underline;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-field__outline\s*\{[^}]*color:\s*rgb\(var\(--v-theme-border\)\)\s*!important;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-field--focused \.v-field__outline\s*\{[^}]*color:\s*rgb\(var\(--v-theme-focusIndicator\)\)\s*!important;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-input--disabled \.v-field[\s\S]*?\.v-selection-control--disabled \.v-label\s*\{[^}]*color:\s*rgb\(var\(--v-theme-textDisabled\)\)\s*!important;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-selection-control--disabled \.v-switch__track\s*\{[^}]*background:\s*rgb\(var\(--v-theme-textDisabled\)\)\s*!important;[^}]*opacity:\s*\.45\s*!important;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.settings-row:has\(\.v-selection-control--disabled\) \.settings-row__label\s*\{[^}]*color:\s*rgb\(var\(--v-theme-textDisabled\)\)\s*!important;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-overlay__content \.v-list-item--active\s*\{[^}]*background:\s*rgb\(var\(--v-theme-selectedSurface\)\)\s*!important;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-overlay__content \.v-list-item:not\(\.v-list-item--active\):hover[\s\S]*?background:\s*rgb\(var\(--v-theme-hoverSurface\)\)\s*!important;/s',
            $styles
        );

        $this->assertStringNotContainsString('.v-select__selections', $styles);
        $this->assertStringNotContainsString('.v-theme--dark.v-card > .v-card__text', $styles);
        $this->assertStringNotContainsString('.v-overlay__content .v-theme--dark.v-list-item', $styles);
    }

    public function test_tabs_and_navigation_active_states_use_semantic_theme_tokens(): void
    {
        $appStyles = file_get_contents(base_path("resources/sass/app.scss"));
        $darkStyles = file_get_contents(base_path("resources/sass/DarkMode.scss"));

        $this->assertMatchesRegularExpression(
            '/#navigation-drawer \.navigation-button\.v-list-item--active\s*\{[^}]*background-color:\s*rgb\(var\(--v-theme-primary\)\);[^}]*color:\s*rgb\(var\(--v-theme-on-primary\)\);/s',
            $appStyles
        );
        $this->assertMatchesRegularExpression(
            '/#navigation-drawer \.navigation-button\.v-list-item--active \.v-list-item-title,[\s\S]*?color:\s*rgb\(var\(--v-theme-on-primary\)\)\s*!important;/s',
            $appStyles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-bottom-navigation\s*\{[^}]*background:\s*rgb\(var\(--v-theme-primary\)\)\s*!important;[^}]*color:\s*rgb\(var\(--v-theme-on-primary\)\)\s*!important;/s',
            $appStyles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-bottom-navigation \.v-btn--active \.v-btn__overlay,[\s\S]*?background:\s*rgb\(var\(--v-theme-on-primary\)\);[^}]*opacity:\s*\.14;/s',
            $appStyles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-bottom-navigation \.v-btn--active\s*\{[^}]*box-shadow:\s*inset 0 3px 0 rgb\(var\(--v-theme-on-primary\)\);[^}]*font-weight:\s*700;/s',
            $appStyles
        );
        $this->assertStringContainsString(
            '.v-btn--active:not(.v-bottom-navigation .v-btn) .v-btn__overlay',
            $appStyles
        );

        $this->assertMatchesRegularExpression(
            '/\.admin-settings-tabs \.v-tab\.v-tab--selected,[\s\S]*?color:\s*rgb\(var\(--v-theme-text\)\)\s*!important;/s',
            $darkStyles
        );
        $this->assertMatchesRegularExpression(
            '/\.admin-settings-tabs \.v-tab\.v-tab--selected \.v-tab__slider\s*\{[^}]*background:\s*rgb\(var\(--v-theme-focusIndicator\)\)\s*!important;/s',
            $darkStyles
        );
        $this->assertMatchesRegularExpression(
            '/#navigation-drawer \.v-list-item--active,[\s\S]*?background:\s*rgb\(var\(--v-theme-primary\)\)\s*!important;[^}]*color:\s*rgb\(var\(--v-theme-on-primary\)\)\s*!important;/s',
            $darkStyles
        );

        $this->assertDoesNotMatchRegularExpression(
            '/(?:admin-settings-tabs|navigation-button\.v-list-item--active|v-bottom-navigation)[\s\S]{0,240}color:\s*(?:white|#fff(?:fff)?)/i',
            $appStyles
        );
    }

    public function test_calendar_and_date_picker_use_current_semantic_dark_state_selectors(): void
    {
        $component = file_get_contents(base_path("resources/js/components/Home/Calendar.vue"));
        $appStyles = file_get_contents(base_path("resources/sass/app.scss"));
        $darkStyles = file_get_contents(base_path("resources/sass/DarkMode.scss"));
        $homeStyles = file_get_contents(base_path("resources/sass/Home/Home.scss"));

        $this->assertMatchesRegularExpression(
            '/\.v-theme--dark\.v-date-picker\s*\{[^}]*background:\s*rgb\(var\(--v-theme-surfaceElevated\)\);[^}]*border:\s*1px solid rgb\(var\(--v-theme-border\)\);/s',
            $darkStyles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-date-picker-month__day--selected \.v-date-picker-month__day-btn\s*\{[^}]*background:\s*rgb\(var\(--v-theme-primary\)\)\s*!important;[^}]*color:\s*rgb\(var\(--v-theme-on-primary\)\)\s*!important;/s',
            $darkStyles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-date-picker-month__day--today:not\(\.v-date-picker-month__day--selected\) \.v-date-picker-month__day-btn\s*\{[^}]*color:\s*rgb\(var\(--v-theme-focusIndicator\)\)\s*!important;[^}]*box-shadow:\s*inset 0 0 0 1px rgb\(var\(--v-theme-focusIndicator\)\)\s*!important;/s',
            $darkStyles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-date-picker-month__day:not\(\.v-date-picker-month__day--selected\) \.v-date-picker-month__day-btn:hover,[\s\S]*?\{[^}]*background:\s*rgb\(var\(--v-theme-hoverSurface\)\)\s*!important;/s',
            $darkStyles
        );
        $this->assertMatchesRegularExpression(
            '/\.v-theme--dark\.v-date-picker[\s\S]*?\.v-btn--disabled\s*\{[^}]*color:\s*rgb\(var\(--v-theme-textDisabled\)\)\s*!important;/s',
            $darkStyles
        );
        $this->assertMatchesRegularExpression(
            '/#calendar[\s\S]*?\.calendar-day\s*\{[^}]*border-color:\s*rgb\(var\(--v-theme-border\)\);/s',
            $darkStyles
        );
        $this->assertMatchesRegularExpression(
            '/\.calendar-day:focus-visible\s*\{[^}]*outline:\s*2px solid rgb\(var\(--v-theme-focusIndicator\)\);[^}]*outline-offset:\s*2px;/s',
            $homeStyles
        );

        $this->assertMatchesRegularExpression(
            '/\.date-picker-dialog\s*\{[\s\S]*?\.v-date-picker\s*\{[^}]*border-radius:\s*0px\s*!important;[^}]*overflow:\s*hidden\s*!important;/s',
            $appStyles
        );
        $this->assertMatchesRegularExpression(
            '/#calendar-popup-date\s*\{[^}]*background-color:\s*rgb\(var\(--v-theme-primary\)\);[^}]*color:\s*rgb\(var\(--v-theme-on-primary\)\);/s',
            $homeStyles
        );
        $this->assertMatchesRegularExpression(
            '/\.calendar-popup-icon-button \.v-icon\s*\{[^}]*color:\s*rgb\(var\(--v-theme-on-primary\)\);/s',
            $homeStyles
        );
        $this->assertStringContainsString(
            'border: 1px solid rgb(var(--v-theme-border));',
            $homeStyles
        );

        $this->assertStringContainsString('role="button"', $component);
        $this->assertStringContainsString('tabindex="0"', $component);
        $this->assertStringContainsString('@keydown.enter.prevent="openCalendarDayPopup($event, day)"', $component);
        $this->assertStringContainsString('@keydown.space.prevent="openCalendarDayPopup($event, day)"', $component);

        foreach ([
            '.v-theme--dark.v-picker__body',
            '.v-date-picker-title',
            '.v-picker.v-card.v-picker--date',
            '.v-theme--light.v-picker__body',
            '.v-date-picker-table--month',
        ] as $obsoleteSelector) {
            $this->assertStringNotContainsString($obsoleteSelector, $darkStyles . $appStyles);
        }
        $this->assertStringNotContainsString('border-color: #404040', $darkStyles);
        $this->assertDoesNotMatchRegularExpression(
            '/#calendar-popup-date\s*\{[^}]*color:\s*white;/s',
            $homeStyles
        );
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

        $appScss = file_get_contents(base_path("resources/sass/app.scss"));
        $this->assertMatchesRegularExpression(
            '/\.app-dialog-card\s*\{[^}]*max-height:\s*calc\(100dvh - 32px\);[^}]*display:\s*flex\s*!important;[^}]*flex-direction:\s*column;/s',
            $appScss
        );
        $this->assertMatchesRegularExpression(
            '/\.app-dialog-card\s*>\s*\.v-card-text\s*\{[^}]*flex:\s*0 1 auto;[^}]*min-height:\s*0;[^}]*overflow-y:\s*auto;/s',
            $appScss
        );
        $this->assertStringContainsString(
            ".app-dialog-card > .v-card-title",
            $appScss,
            "Dialog titles and actions must remain outside the scrollable body."
        );
        $this->assertStringContainsString(".app-dialog-card > .v-card-actions", $appScss);
        $this->assertStringNotContainsString(".app-dialog-card > .v-card__text", $appScss);
        $this->assertStringNotContainsString(".app-dialog-card > .v-card__actions", $appScss);
    }

    public function test_vocabulary_edit_metadata_chips_keep_visible_labels_and_semantic_contrast(): void
    {
        $dialog = file_get_contents(base_path("resources/js/components/Vocabulary/VocabularyEditDialog.vue"));
        $styles = file_get_contents(base_path("resources/sass/Vocabulary/VocabularyEditDialog.scss"));

        foreach ([
            "Added on {{ item.added_to_srs }}",
            "Finished review",
            "Due on {{ item.next_review }}",
            "{{ item.lookup_count }} lookups",
        ] as $label) {
            $this->assertStringContainsString($label, $dialog);
        }

        $this->assertGreaterThanOrEqual(4, substr_count($dialog, 'class="vocabulary-meta-chip'));
        $this->assertGreaterThanOrEqual(4, substr_count($dialog, 'variant="flat"'));
        $this->assertStringContainsString(".vocabulary-meta-chip.v-chip", $styles);
        $this->assertStringContainsString("rgb(var(--v-theme-on-primary))", $styles);
        $this->assertStringContainsString("rgb(var(--v-theme-primary))", $styles);
    }

    public function test_reader_settings_use_a_shared_responsive_control_row_contract(): void
    {
        $dialog = file_get_contents(base_path("resources/js/components/TextReader/TextReaderSettings.vue"));
        $styles = file_get_contents(base_path("resources/sass/TextReader/TextReader.scss"));

        $this->assertGreaterThanOrEqual(20, substr_count($dialog, 'class="settings-row'));
        $this->assertGreaterThanOrEqual(6, substr_count($dialog, 'settings-row--slider'));
        $this->assertGreaterThanOrEqual(12, substr_count($dialog, 'settings-row--toggle'));
        $this->assertGreaterThanOrEqual(7, substr_count($dialog, 'settings-row__label'));
        $this->assertGreaterThanOrEqual(20, substr_count($dialog, 'settings-row__control'));
        $this->assertGreaterThanOrEqual(5, substr_count($dialog, 'settings-row__help'));
        $this->assertGreaterThanOrEqual(5, substr_count($dialog, '<v-btn icon variant="text" size="small" class="settings-row__help"'));
        $this->assertGreaterThanOrEqual(19, substr_count($dialog, 'hide-details'));
        $this->assertStringContainsString(':show-arrows="$vuetify.display.smAndDown"', $dialog);

        $this->assertStringContainsString('v-slot:activator="{ props }"', $dialog);
        $this->assertStringNotContainsString('v-slot:activator="{ on, attrs }"', $dialog);
        $this->assertGreaterThanOrEqual(20, substr_count($dialog, '@update:model-value="saveSettings'));

        $this->assertMatchesRegularExpression(
            '/#text-reader-settings\s*\{.*?\.settings-row\s*\{[^}]*display:\s*grid;[^}]*grid-template-columns:\s*minmax\(180px,\s*260px\)\s+minmax\(0,\s*1fr\);/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.settings-row__control\s*\{[^}]*min-width:\s*0;[^}]*max-width:\s*560px\s*!important;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.settings-row__label\s*\{[^}]*min-width:\s*0;[^}]*max-width:\s*none\s*!important;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.settings-row--toggle\s+\.settings-row__control\s*\{[^}]*justify-self:\s*start;/s',
            $styles
        );
        $this->assertMatchesRegularExpression(
            '/\.settings-row--slider\s+\.settings-row__control\s*\{[^}]*padding-top:\s*24px;/s',
            $styles
        );
        $this->assertStringContainsString(".settings-row--slider .v-slider-thumb__label", $styles);
        $this->assertStringNotContainsString(".settings-row--slider .v-slider__thumb-label", $styles);
        $this->assertStringNotContainsString("translateY(", strstr($styles, "#text-reader-settings"));
        $this->assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*600px\)\s*\{.*?\.settings-row\s*\{[^}]*grid-template-columns:\s*minmax\(0,\s*1fr\);/s',
            $styles
        );
        $this->assertStringContainsString("> .v-card-text", $styles);
        $this->assertStringNotContainsString("#text-reader-settings {\n    .v-card__text", $styles);
        $this->assertStringNotContainsString(":has(.v-switch)", $styles);
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
