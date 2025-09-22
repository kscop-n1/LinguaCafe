<?php

namespace App\Services\Vocabulary;

use App\DataTransferObjects\Vocabulary\VocabularySearchResultData;
use App\Enums\ChapterProcessingStatusEnum;
use App\Helpers\Language\LanguageConfig;
use App\Models\Book;
use App\Models\User;
use App\Queries\VocabularySearchQuery;

class VocabularyService
{
    private $itemsPerPage;

    public function __construct()
    {
        $this->itemsPerPage = 30;
    }

    public function searchVocabulary(
        User $user,
        LanguageConfig $language,
        string $text,
        int $bookId,
        int $chapterId,
        int $stage,
        string $phrases,
        string $orderBy,
        string $translation,
        int $page
    ): VocabularySearchResultData {
        // get books and chapters
        $books = Book::query()
            ->where('user_id', $user->id)
            ->where('language', $language->name)
            ->with('chapters', function ($query) {
                $query->select(['id', 'name', 'book_id']);
                $query->where('processing_status', ChapterProcessingStatusEnum::PROCESSED->value);
            })
            ->get();

        $search = (new VocabularySearchQuery())->retrieve(
            $user->id,
            $language->name,
            $text,
            $bookId,
            $chapterId,
            $stage,
            $phrases,
            $orderBy,
            $translation
        );

        $data = new \stdClass();
        $data->wordCount = $search->count();
        $data->words = $search->skip(($page - 1) * $this->itemsPerPage)->take($this->itemsPerPage)->get();
        $data->books = $books;
        $data->pageCount = ceil($data->wordCount / $this->itemsPerPage);
        $data->currentPage = $page;
        $data->languageSpaces = $language->hasSpaces();

        return new VocabularySearchResultData(
            wordCount: $data->wordCount = $search->count(),
            words: $search->skip(($page - 1) * $this->itemsPerPage)->take($this->itemsPerPage)->get(),
            books: $books,
            pageCount: ceil($data->wordCount / $this->itemsPerPage),
            currentPage: $page,
            languageSpaces: $language->hasSpaces(),
        );
    }
}
