<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    /**
     * List every brand.
     */
    public function index(): Response
    {
        return Inertia::render('admin/brands/Index', [
            'brands' => Brand::query()->withCount('watches')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a new brand.
     */
    public function store(BrandRequest $request): RedirectResponse
    {
        $brand = Brand::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$brand->name} was created."]);

        return back();
    }

    /**
     * Update a brand.
     */
    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand updated.']);

        return back();
    }

    /**
     * Delete a brand that no longer has any watches.
     */
    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->watches()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "{$brand->name} still has watches. Move or delete them first."]);

            return back();
        }

        $brand->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$brand->name} was deleted."]);

        return back();
    }
}
