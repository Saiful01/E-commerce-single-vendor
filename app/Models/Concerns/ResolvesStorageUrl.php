<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

trait ResolvesStorageUrl
{
    /**
     * Public URL on local disks, signed temporary URL on private S3.
     */
    protected function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = config('filesystems.default');

        return $disk === 's3'
            ? Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(60))
            : Storage::disk($disk)->url($path);
    }
}
