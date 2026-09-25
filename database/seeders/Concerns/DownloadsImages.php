<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Downloads demo photography onto the public disk, skipping files that already exist.
 */
trait DownloadsImages
{
    protected function downloadImage(string $url, string $path): ?string
    {
        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            return $path;
        }

        try {
            $response = Http::timeout(30)->retry(2, 500)->get($url);
        } catch (Throwable) {
            $this->command->warn("Could not download {$url}");

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $disk->put($path, $response->body());

        return $path;
    }

    /**
     * Build an Unsplash image URL with the given crop parameters.
     *
     * @param  array<string, string|int|float>  $parameters
     */
    protected function unsplashUrl(string $photoId, array $parameters = []): string
    {
        return "https://images.unsplash.com/photo-{$photoId}?".http_build_query([
            'fm' => 'jpg',
            'q' => 80,
            'fit' => 'crop',
            ...$parameters,
        ]);
    }
}
