<?php

namespace App\Services\Manual;

class ManualService
{
    public function getManualTree(): array
    {
        $manualTree = [];

        $path = public_path('./../manual/');
        $files = scandir($path);

        $index = 0;
        foreach ($files as $file) {
            // skip
            if ($file === '.' || $file === '..') {
                continue;
            }

            // create page;
            $page = new \stdClass;
            $page->id = $index;
            $page->name = str_replace('.md', '', $file);
            $page->fileName = str_replace('.md', '', $file);
            $page->level = 0;
            $index++;

            // get subpages
            $subPages = [];
            $handle = fopen('./../manual/' . $file, 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    // if line starts with "# "
                    if (strpos($line, '# ') === 0) {
                        $subPageName = substr($line, 2);
                        $subPageName = str_replace("\r\n", '', $subPageName);
                        $subPageName = str_replace("\n", '', $subPageName);
                        $subPageName = str_replace("\n", '', $subPageName);

                        $subPage = new \stdClass;
                        $subPage->id = $index;
                        $subPage->name = $subPageName;
                        $subPage->fileName = str_replace('.md', '', $file) . '#' . $subPageName;
                        $subPage->level = 1;
                        $subPages[] = $subPage;
                        $index++;
                    }
                }

                fclose($handle);
            }

            if (count($subPages)) {
                $page->children = $subPages;
            }

            $manualTree[] = $page;
        }

        return $manualTree;
    }
}
