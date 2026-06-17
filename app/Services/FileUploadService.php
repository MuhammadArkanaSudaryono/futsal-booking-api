<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload file ke storage dan kembalikan path-nya.
     *
     * @param  UploadedFile  $file
     * @param  string        $folder   Folder di dalam storage/app/public/
     * @return string                  Path relatif, contoh: "avatars/uuid.jpg"
     */
    public function upload(UploadedFile $file, string $folder): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs($folder, $filename, 'public');

        return $path;
    }

    /**
     * Hapus file dari storage berdasarkan path relatif.
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Ganti file lama dengan file baru.
     * Hapus file lama, upload file baru, kembalikan path baru.
     */
    public function replace(UploadedFile $newFile, ?string $oldPath, string $folder): string
    {
        $this->delete($oldPath);

        return $this->upload($newFile, $folder);
    }
}
