<?php

namespace App\Http\Controllers\Manual;

use App\Http\Controllers\Controller;
use App\Services\Manual\ManualService;

class ManualController extends Controller
{
    public function __construct(
        private ManualService $manualService
    ) {
        //
    }

    public function getUserManualTree()
    {
        $manualTree = $this->manualService->getManualTree();

        return response()->json($manualTree);
    }

    public function getUserManualFile(string $fileName)
    {
        return response()->file('./../manual/' . $fileName . '.md');
    }
}
