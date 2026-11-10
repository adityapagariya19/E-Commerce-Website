<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Support\ImageUploader;
use App\Support\Slug;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', ['categories' => Category::with('parent')->withCount('products')->orderBy('parent_id')->orderBy('position')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true]), 'parents' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $c = new Category();
        $this->save($request, $c);
        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', ['category' => $category, 'parents' => Category::where('id', '!=', $category->id)->whereNotIn('id', $category->descendantIds())->orderBy('name')->get()]);
    }

    public function update(Request $request, Category $category)
    {
        $this->save($request, $category);
        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        Category::where('parent_id', $category->id)->update(['parent_id' => $category->parent_id]);
        ImageUploader::delete($category->image);
        ActivityLog::record('category.deleted', $category, ['name' => $category->name]);
        $category->delete(); // pivot rows cascade; products remain
        return back()->with('success', 'Category deleted. Its products were kept.');
    }

    private function save(Request $request, Category $c): void
    {
        $forbidden = $c->exists ? $c->descendantIds() : [];
        $d = $request->validate([
            'name' => ['required', 'max:120'], 'slug' => ['nullable', 'max:140', Rule::unique('categories', 'slug')->ignore($c->id)],
            'parent_id' => ['nullable', 'exists:categories,id', Rule::notIn($forbidden)], 'description' => ['nullable', 'max:1000'],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'], 'image' => ImageUploader::rules(),
        ]);
        $c->fill([
            'name' => $d['name'], 'slug' => Slug::unique(Category::class, ($d['slug'] ?? null) ?: $d['name'], $c->id), 'parent_id' => $d['parent_id'] ?? null,
            'description' => $d['description'] ?? null, 'position' => $d['position'] ?? 0, 'is_active' => $request->boolean('is_active'),
            'image' => ImageUploader::replace($request->file('image'), $c->image, 'categories'),
        ])->save();
        ActivityLog::record($c->wasRecentlyCreated ? 'category.created' : 'category.updated', $c, ['name' => $c->name]);
    }
}
