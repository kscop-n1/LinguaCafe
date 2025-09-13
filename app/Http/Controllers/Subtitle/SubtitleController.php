<?php

namespace App\Http\Controllers\Subtitle;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subtitle\ParseSubtitleFileRequest;
use App\Services\Subtitle\SubtitleService;
use App\Services\TempFileService;
use Illuminate\Support\Facades\Auth;

class SubtitleController extends Controller
{
    public function __construct(
        private SubtitleService $subtitleService,
        private TempFileService $tempFileService,
    ) {
        //
    }

    public function parseSubtitleFile(ParseSubtitleFileRequest $request)
    {
        $subtitleFile = $request->file('subtitleFile');
        $user = Auth::user();

        try {
            $fileName = $this->tempFileService->moveFileToTempFolder($user, $subtitleFile);

            $subtitleContent = $this->subtitleService->getSubtitleFileContent(storage_path('app/temp') . '/' . $fileName);
        } catch (\Throwable $error) {
            $this->tempFileService->deleteTempFile($fileName);

            throw new \Throwable($error->getMessage());
        }

        $this->tempFileService->deleteTempFile($fileName);

        return response()->json([
            'data' => $subtitleContent,
        ]);
    }
}
