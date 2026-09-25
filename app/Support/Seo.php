<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Builds the search and social metadata passed to every storefront page.
 */
class Seo
{
    /**
     * @param  list<array<string, mixed>>  $jsonLd
     * @return array{title: string|null, description: string, image: string|null, url: string, type: string, robots: string, jsonLd: list<array<string, mixed>>}
     */
    public static function page(
        ?string $title = null,
        ?string $description = null,
        ?string $image = null,
        string $type = 'website',
        array $jsonLd = [],
        bool $indexable = true,
    ): array {
        $settings = app(SiteSettings::class);
        $page = request()->integer('page');

        return [
            'title' => $title,
            'description' => Str::limit(
                Str::squish(strip_tags($description ?: (string) $settings->get('meta_description'))),
                160,
            ),
            'image' => $image ?? $settings->imageUrl('og_image_path') ?? $settings->imageUrl('hero_image_path'),
            'url' => $page > 1 ? url()->current().'?page='.$page : url()->current(),
            'type' => $type,
            'robots' => $indexable ? 'index, follow' : 'noindex, follow',
            'jsonLd' => array_map(
                fn (array $item): array => array_filter($item, fn (mixed $value): bool => $value !== null && $value !== ''),
                $jsonLd,
            ),
        ];
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
    }
}
