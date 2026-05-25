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

    /**
     * @param array<int, string> $directories
     * @return array<int, string>
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
