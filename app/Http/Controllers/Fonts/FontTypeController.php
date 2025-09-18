<?php

namespace App\Http\Controllers\Fonts;

use App\Helpers\Language\LanguageConfig;
use App\Http\Controllers\Controller;
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

    public function index()
    {
        $fonts = $this->fontTypeService->getInstalledFontTypes();

        return new FontTypeResourceCollection($fonts);
    }

    public function indexForLanguage(string $language)
    {
        $language = LanguageConfig::load($language);

        $fonts = $this->fontTypeService->getFontTypesForLanguage($language);

        return new FontTypeResourceCollection($fonts);
    }

    public function show(FontType $fontType)
    {
        if (mb_strpos($fontType->filename, 'Default') === 0) {
            $imagePath = Storage::disk('default-files')->path('/fonts/' . $fontType->filename);
        } else {
            $imagePath = Storage::path('/fonts/' . $fontType->filename);
        }

        return response()->file($imagePath);
    }

    public function store(CreateFontTypeRequest $request)
    {
        $fontFile = $request->file('fontFile');
        $fontName = $request->validated('name');
        $fontLanguages = $request->validated('languages');

        $this->fontTypeService->createFontType($fontFile, $fontName, $fontLanguages);

        return response()->noContent();
    }

    public function update(UpdateFontTypeRequest $request, FontType $fontType)
    {
        $fontName = $request->validated('name');
        $fontLanguages = $request->validated('languages');

        $this->fontTypeService->updateFontType($fontType, $fontName, $fontLanguages);

        return response()->noContent();
    }

    public function destroy(FontType $fontType)
    {
        $this->fontTypeService->deleteFontType($fontType);

        return response()->noContent();
    }
}
