<?php

namespace App\Services;

use App\Helpers\Language\LanguageConfig;
use App\Models\FontType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FontTypeService
{
    public function __construct()
    {
        //
    }

    public function getInstalledFontTypes(): Collection
    {
        return FontType::get();
    }

    public function getFontTypesForLanguage(LanguageConfig $language): Collection
    {
        $fonts = FontType::query()
            ->orderBy('default', 'desc')
            ->get();

        $fonts = $fonts->filter(function ($font) use ($language) {
            $fontLanguages = collect(json_decode($font->languages));

            return $fontLanguages->contains(Str::ucfirst($language->name));
        });

        return $fonts->values();
    }

    public function createFontType(UploadedFile $fontFile, string $fontName, array $fontLanguages): void
    {
        /*
            File names that start with Default must be renamed.
            Those file names are reserved for default font names.
        */
        $fileName = str_replace('Default', 'NotDefault', $fontFile->getClientOriginalName());

        if (Storage::exists('fonts/' . $fileName)) {
            throw new \Exception('The font file already exists on the server.');
        }

        $fontFile->move(storage_path('app/fonts'), $fileName);

        $fontType = new FontType;
        $fontType->filename = $fileName;
        $fontType->name = $fontName;
        $fontType->languages = json_encode($fontLanguages);
        $fontType->default = false;
        $fontType->save();
    }

    public function updateFontType(FontType $fontType, string $fontName, array $fontLanguages): void
    {
        $fontType->name = $fontName;
        $fontType->languages = json_encode($fontLanguages);
        $fontType->save();
    }

    public function deleteFontType(FontType $fontType): void
    {
        Storage::delete('fonts/' . $fontType->filename);

        $fontType->delete();
    }
}
