<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\StoresPublicImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use StoresPublicImages;

    /**
     * List every category.
     */
    public function index(): Response
    {
        return Inertia::render('admin/categories/Index', [
            'categories' => Category::query()->ordered()->withCount('watches')->get(),
        ]);
    }

    /**
     * Store a new category.
     */
    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = new Category($request->safe()->only(['name', 'slug', 'description']));
        $category->sort_order = $request->integer('sort_order');

        if ($request->hasFile('image')) {
            $category->image_path = $this->storePublicImage($request->file('image'), 'categories');
        }

        $category->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$category->name} was created."]);

        return back();
    }

    /**
     * Update a category.
     */
    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->fill($request->safe()->only(['name', 'slug', 'description']));
        $category->sort_order = $request->integer('sort_order');

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }

            $category->image_path = $request->hasFile('image')
                ? $this->storePublicImage($request->file('image'), 'categories')
                : null;
        }

        $category->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category updated.']);

        return back();
    }

    /**
     * Delete a category. Its watches stay in the catalogue without a category.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$category->name} was deleted."]);

        return back();
    }
}
