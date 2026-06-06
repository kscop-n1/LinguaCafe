<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    public function createBackup(BackupService $backupService)
    {
        try {
            $result = $backupService->createBackup();
        } catch (\Throwable $exception) {
            Log::error('Database backup request failed unexpectedly.', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return $this->backupFailureResponse();
        }

        if (($result['success'] ?? false) !== true) {
            return $this->backupFailureResponse();
        }

        return response()->json([
            'success' => true,
            'filename' => $result['filename'],
        ], 200);
    }

    private function backupFailureResponse()
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'BACKUP_EXPORT_FAILED',
                'message' => 'Database backup could not be created.',
            ],
        ], 500);
    }
}
