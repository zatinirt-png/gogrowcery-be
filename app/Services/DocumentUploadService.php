<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentUploadService
{
    private string $disk = 'r2';

    public function uploadKtp(UploadedFile $file, string $username): string
    {
        return $this->upload($file, "suppliers/{$username}/ktp");
    }

    public function uploadNpwp(UploadedFile $file, string $username): string
    {
        return $this->upload($file, "suppliers/{$username}/npwp");
    }

    public function delete(string $path): void
    {
        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }
    }

    private function upload(UploadedFile $file, string $folder): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = "{$folder}/{$filename}";

        Storage::disk($this->disk)->put($path, file_get_contents($file), 'private');

        return $path;
    }
}
