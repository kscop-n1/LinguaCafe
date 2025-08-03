<?php

namespace App\Http\Requests\Import;

use App\Enums\Import\EbookChapterSortMethodEnum;
use App\Enums\Import\ImportTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'importType' => [
                'required',
                Rule::enum(ImportTypeEnum::class),
            ],
            'eBookChapterSortMethod' => [
                'required',
                Rule::enum(EbookChapterSortMethodEnum::class),
            ],
            'bookId' => [
                'required',
                'numeric',
                'gte:-1',
            ],
            'bookName' => [
                'nullable',
                'string',
            ],
            'chapterName' => [
                'required',
                'string',
            ],
            'maximumCharactersPerChapter' => [
                'required',
                'numeric',
                'gte:200',
                'lte:20000',
            ],
            'importText' => [
                'nullable',
                'string',
            ],
            'importSubtitles' => [
                'nullable',
                'string',
            ],
            'importFile' => [
                'nullable',
                'file',
            ],
        ];
    }
}
