<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ImageUrl
{
    /**
     * URL for an image path as stored in the database. Paths of uploaded files
     * are relative to the public disk ("products/abc.jpg"); a full URL or an
     * absolute path is used as it is.
     */
    public static function for(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#^(https?:)?//#', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
