<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Support\ImageUploader;
use App\Support\Slug;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index() { return view('admin.brands.index', ['brands' => Brand::withCount('products')->orderBy('name')->get()]); }
    public function create() { return view('admin.brands.form', ['brand' => new Brand(['is_active' => true])]); }
    public function edit(Brand $brand) { return view('admin.brands.form', compact('brand')); }

    public function store(Request $request) { $this->save($request, new Brand()); return redirect()->route('admin.brands.index')->with('success', 'Brand created.'); }
    public function update(Request $request, Brand $brand) { $this->save($request, $brand); return redirect()->route('admin.brands.index')->with('success', 'Brand updated.'); }

    public function destroy(Brand $brand)
    {
        ImageUploader::delete($brand->logo);
        ActivityLog::record('brand.deleted', $brand, ['name' => $brand->name]);
        $brand->delete();
        return back()->with('success', 'Brand deleted.');
    }

    private function save(Request $request, Brand $b): void
    {
        $d = $request->validate(['name' => ['required', 'max:120'], 'slug' => ['nullable', 'max:140', Rule::unique('brands', 'slug')->ignore($b->id)], 'logo' => ImageUploader::rules()]);
        $b->fill(['name' => $d['name'], 'slug' => Slug::unique(Brand::class, ($d['slug'] ?? null) ?: $d['name'], $b->id), 'is_active' => $request->boolean('is_active'),
            'logo' => ImageUploader::replace($request->file('logo'), $b->logo, 'brands')])->save();
        ActivityLog::record($b->wasRecentlyCreated ? 'brand.created' : 'brand.updated', $b, ['name' => $b->name]);
    }
}
