<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\StoresPublicImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WatchImageRequest;
use App\Models\Watch;
use App\Models\WatchImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class WatchImageController extends Controller
{
    use StoresPublicImages;

    /**
     * Upload additional photos for a watch.
     */
    public function store(WatchImageRequest $request, Watch $watch): RedirectResponse
    {
        $position = (int) $watch->images()->max('sort_order') + 1;

        foreach ($request->file('images') as $index => $file) {
            $watch->images()->create([
                'path' => $this->storePublicImage($file, "watches/{$watch->id}"),
                'alt' => $watch->name,
                'sort_order' => $position + $index,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Photos uploaded.']);

        return back();
    }

    /**
     * Update a photo's alternative text.
     */
    public function update(Request $request, Watch $watch, WatchImage $image): RedirectResponse
    {
        $image->update($request->validate([
            'alt' => ['nullable', 'string', 'max:160'],
        ]));

        return back();
    }

    /**
     * Save a new photo order. The first photo becomes the cover.
     */
    public function reorder(Request $request, Watch $watch): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', Rule::exists('watch_images', 'id')->where('watch_id', $watch->id)],
        ]);

        DB::transaction(function () use ($validated, $watch): void {
            foreach ($validated['order'] as $position => $id) {
                $watch->images()->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return back();
    }

    /**
     * Delete a photo.
     */
    public function destroy(Watch $watch, WatchImage $image): RedirectResponse
    {
        $image->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Photo removed.']);

        return back();
    }
}
