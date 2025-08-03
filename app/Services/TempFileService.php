<?php

namespace App\Services;

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class TempFileService
{
    public function moveFileToTempFolder(User $user, UploadedFile $importFile): string
    {
        $randomString = bin2hex(openssl_random_pseudo_bytes(30));
        $extension = '.' . $importFile->getClientOriginalExtension();
        $fileName = $user->id . '_' . $randomString . $extension;
        $importFile->move(storage_path('app/temp'), $fileName);

        return $fileName;
    }

    public function deleteTempFile(string $fileName): void
    {
        File::delete(storage_path('app/temp') . '/' . $fileName);
    }
}
