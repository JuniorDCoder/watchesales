<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\StoresPublicImages;
use App\Enums\WatchCondition;
use App\Enums\WatchGender;
use App\Enums\WatchMovement;
use App\Enums\WatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WatchRequest;
use App\Http\Resources\WatchDetailResource;
use App\Http\Resources\WatchResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Watch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WatchController extends Controller
{
    use StoresPublicImages;

    /**
     * List every watch, including unpublished ones.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->filled('search') ? (string) $request->string('search') : null,
            'status' => WatchStatus::tryFrom((string) $request->string('status'))?->value,
            'category' => $request->filled('category') ? (string) $request->string('category') : null,
            'brand' => $request->filled('brand') ? (string) $request->string('brand') : null,
        ];

        $watches = Watch::query()
            ->filter($filters)
            ->when($filters['status'], fn ($query, string $status) => $query->where('status', $status))
            ->with(['brand', 'category', 'primaryImage'])
            ->withCount('inquiries')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/watches/Index', [
            'watches' => WatchResource::collection($watches),
            'filters' => $filters,
            'options' => $this->options(),
        ]);
    }

    /**
     * Show the form for adding a watch.
     */
    public function create(): Response
    {
        return Inertia::render('admin/watches/Form', [
            'watch' => null,
            'options' => $this->options(),
        ]);
    }

    /**
     * Store a new watch along with any uploaded photos.
     */
    public function store(WatchRequest $request): RedirectResponse
    {
        $watch = DB::transaction(function () use ($request): Watch {
            $watch = Watch::query()->create($request->safe()->except('images'));

            $this->storeImages($watch, $request->file('images', []));

            return $watch;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$watch->name} was added."]);

        return to_route('admin.watches.edit', $watch);
    }

    /**
     * Show the form for editing a watch.
     */
    public function edit(Request $request, Watch $watch): Response
    {
        $watch->load(['brand', 'category', 'images', 'primaryImage'])->loadCount('inquiries');

        return Inertia::render('admin/watches/Form', [
            'watch' => (new WatchDetailResource($watch))->resolve($request),
            'options' => $this->options(),
        ]);
    }

    /**
     * Update a watch.
     */
    public function update(WatchRequest $request, Watch $watch): RedirectResponse
    {
        DB::transaction(function () use ($request, $watch): void {
            $watch->update($request->safe()->except('images'));

            $this->storeImages($watch, $request->file('images', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Changes saved.']);

        return to_route('admin.watches.edit', $watch);
    }

    /**
     * Delete a watch and its photos.
     */
    public function destroy(Watch $watch): RedirectResponse
    {
        $watch->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$watch->name} was deleted."]);

        return to_route('admin.watches.index');
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeImages(Watch $watch, array $files): void
    {
        $nextPosition = (int) $watch->images()->max('sort_order') + ($watch->images()->exists() ? 1 : 0);

        foreach (array_values($files) as $index => $file) {
            $watch->images()->create([
                'path' => $this->storePublicImage($file, "watches/{$watch->id}"),
                'alt' => $watch->name,
                'sort_order' => $nextPosition + $index,
            ]);
        }
    }

    /**
     * Select options shared by the watch list and form.
     *
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'brands' => Brand::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'categories' => Category::query()->ordered()->toBase()->get(['id', 'name', 'slug']),
            'statuses' => WatchStatus::options(),
            'conditions' => WatchCondition::options(),
            'movements' => WatchMovement::options(),
            'genders' => WatchGender::options(),
        ];
    }
}
