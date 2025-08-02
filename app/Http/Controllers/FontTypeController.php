<?php

namespace App\Http\Controllers;

use App\Helpers\Language\LanguageConfig;
use App\Http\Requests\FontTypes\CreateFontTypeRequest;
use App\Http\Requests\FontTypes\UpdateFontTypeRequest;
use App\Http\Resources\Font\FontTypeResourceCollection;
use App\Models\FontType;
use App\Services\FontTypeService;
use Illuminate\Support\Facades\Storage;

class FontTypeController extends Controller
{
    public function __construct(
        private FontTypeService $fontTypeService
    ) {
        //
    }

    public function getInstalledFontTypes()
    {
        $fonts = $this->fontTypeService->getInstalledFontTypes();

        return new FontTypeResourceCollection($fonts);
    }

    public function downloadFontTypeFile(string $fileName)
    {
        if (mb_strpos($fileName, 'Default') === 0) {
            $imagePath = Storage::disk('default-files')->path('/fonts/' . $fileName);
        } else {
            $imagePath = Storage::path('/fonts/' . $fileName);
        }

        return response()->file($imagePath);
    }

    public function getFontTypesForLanguage(string $language)
    {
        $language = LanguageConfig::load($language);

        $fonts = $this->fontTypeService->getFontTypesForLanguage($language);

        return new FontTypeResourceCollection($fonts);
    }

    public function createFontType(CreateFontTypeRequest $request)
    {
        $fontFile = $request->file('fontFile');
        $fontName = $request->validated('name');
        $fontLanguages = $request->validated('languages');

        $this->fontTypeService->createFontType($fontFile, $fontName, $fontLanguages);

        return response()->noContent();
    }

    public function updateFontType(UpdateFontTypeRequest $request, FontType $fontType)
    {
        $fontName = $request->validated('name');
        $fontLanguages = $request->validated('languages');

        $this->fontTypeService->updateFontType($fontType, $fontName, $fontLanguages);

        return response()->noContent();
    }

    public function deleteFontType(FontType $fontType)
    {
        $this->fontTypeService->deleteFontType($fontType);

        return response()->noContent();
    }
}
