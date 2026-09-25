<?php

namespace App\Concerns;

use Illuminate\Http\UploadedFile;
use RuntimeException;

trait StoresPublicImages
{
    /**
     * Store an uploaded image on the public disk under a generated name.
     *
     * @throws RuntimeException when the file could not be written.
     */
    protected function storePublicImage(UploadedFile $file, string $directory): string
    {
        $path = $file->store($directory, 'public');

        if ($path === false) {
            throw new RuntimeException("Unable to store the uploaded image in [{$directory}].");
        }

        return $path;
    }
}
