<?php

namespace App\Services\Vocabulary;

use App\DataTransferObjects\Vocabulary\CsvImportResultData;
use App\Helpers\Language\LanguageConfig;
use App\Models\EncounteredWord;
use App\Models\User;
use App\Queries\VocabularySearchQuery;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Writer;

class VocabularyImportExportService
{
    public function export(
        User $user,
        LanguageConfig $language,
        $text,
        $bookId,
        $chapterId,
        $stage,
        $phrases,
        $orderBy,
        $translation,
        $fields
    ): Writer {
        $words = (new VocabularySearchQuery())->retrieve(
            $user->id,
            $language->name,
            $text,
            $bookId,
            $chapterId,
            $stage,
            $phrases,
            $orderBy,
            $translation
        )->get();

        $csv = Writer::createFromFileObject(new \SplTempFileObject());
        $csv->setDelimiter('|');

        // headers
        $csvArray = [];
        foreach ($fields as $field) {
            if ($field['export']) {
                $csvArray[] = str_replace('Stage', 'Level', $field['headerName']);
            }
        }

        $csv->insertOne($csvArray);

        // insert data to csv
        $phraseWordDelimiter = $language->hasSpaces() ? ' ' : '';
        foreach ($words as $word) {
            $csvArray = [];
            foreach ($fields as $field) {
                if (!$field['export']) {
                    continue;
                }

                $searchObjectProperty = $field['searchObjectProperty'];

                if ($word->type === 'phrase' && $searchObjectProperty === 'word') {
                    $csvArray[] = implode($phraseWordDelimiter, json_decode($word->$searchObjectProperty));
                } else {
                    $csvArray[] = $word->$searchObjectProperty;
                }
            }

            $csv->insertOne($csvArray);
        }

        return $csv;
    }

    public function import(
        User $user,
        LanguageConfig $language,
        string $fileName,
        string $delimiter,
        bool $onlyUpdate,
        bool $skipHeader
    ): CsvImportResultData {
        $stageMapping = [
            'new' => 2,
            'ignored' => 1,
            'learned' => 0,
            '1' => -1,
            '2' => -2,
            '3' => -3,
            '4' => -4,
            '5' => -5,
            '6' => -6,
            '7' => -7,
        ];

        DB::disableQueryLog();
        $reader = Reader::createFromPath(storage_path('app/temp') . '/' . $fileName, 'r');
        $reader->setDelimiter($delimiter);
        $records = $reader->getRecords();
        $createdWords = 0;
        $updatedWords = 0;
        $rejectedWords = 0;

        // collect data from csv file
        DB::beginTransaction();
        foreach ($records as $index => $record) {
            $lowerCaseWord = mb_strtolower($record[0]);

            // skip header if option is enabled
            if ($index === 0 && $skipHeader) {
                continue;
            }

            // reject word if contains space character
            if (str_contains($lowerCaseWord, ' ')) {
                $rejectedWords++;

                continue;
            }

            // reject word if it's too long
            if (mb_strlen($lowerCaseWord) >= 255) {
                $rejectedWords++;

                continue;
            }

            // reject word if word field is missing
            if (mb_strlen($lowerCaseWord) === 0) {
                $rejectedWords++;

                continue;
            }

            // reject word if it's stage is stage is an incorrect value
            $stage = isset($record[5]) ? $record[5] : 'learned';
            if (isset($record[5]) && !isset($stageMapping[$stage])) {
                $rejectedWords++;

                continue;
            }

            // try to retrieve word
            $encounteredWord = EncounteredWord::query()
                ->where('user_id', '=', $user->id)
                ->where('language', '=', $language->name)
                ->where('word', '=', $lowerCaseWord)
                ->first();

            // if does not exist, create it
            if (!$encounteredWord) {

                // reject word if does not exist and only update option is used
                if ($onlyUpdate) {
                    $rejectedWords++;

                    continue;
                }

                $encounteredWord = new EncounteredWord();
                $encounteredWord->user_id = $user->id;
                $encounteredWord->language = $language->name;
                $encounteredWord->word = $lowerCaseWord;
                $encounteredWord->translation = '';
                $encounteredWord->lemma = '';
                $encounteredWord->lemma_reading = '';
                $encounteredWord->reading = '';
                $encounteredWord->stage = 0;
                $encounteredWord->kanji = '';

                $createdWords++;
            } else {
                $updatedWords++;
            }

            // set translation
            if (isset($record[1])) {
                $encounteredWord->translation = $record[1];
            }

            // set lemma
            if (isset($record[2])) {
                $encounteredWord->lemma = $record[2];
            }

            // set reading
            if (isset($record[3])) {
                $encounteredWord->reading = $record[3];
            }

            // set lemma reading
            if (isset($record[4])) {
                $encounteredWord->lemma_reading = $record[4];
            }

            // set stage
            if (isset($record[5])) {
                $encounteredWord->setStage($stageMapping[$stage], true);
            }

            // save word with new data
            $encounteredWord->save();

            // add word to accepted words list
            $acceptedWords[] = $lowerCaseWord;
        }

        DB::commit();

        return new CsvImportResultData(
            createdWords: $createdWords,
            updatedWords: $updatedWords,
            rejectedWords: $rejectedWords,
        );
    }
}
