<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ImageUploader;
use App\Support\Slug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::with(['images', 'categories', 'variants'])
            ->when($request->filled('q'), function ($w) use ($request) {
                $like = '%'.addcslashes($request->query('q'), '%_\\').'%';
                $w->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('sku', 'like', $like));
            })
            ->when($request->filled('status'), fn ($w) => $w->where('status', $request->query('status')))
            ->when($request->filled('category'), fn ($w) => $w->whereHas('categories', fn ($c) => $c->where('categories.id', $request->query('category'))));
        $sort = in_array($request->query('sort'), ['name', 'price', 'stock_qty', 'created_at'], true) ? $request->query('sort') : 'created_at';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';
        return view('admin.products.index', ['products' => $q->orderBy($sort, $dir)->paginate(20)->withQueryString(), 'categories' => Category::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.products.form', ['product' => new Product(['status' => 'draft', 'type' => 'simple', 'manage_stock' => true]), 'categories' => Category::orderBy('name')->get(), 'brands' => Brand::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $product = DB::transaction(function () use ($request, $data) {
            $product = new Product();
            $this->fill($product, $data, $request);
            return $product;
        });
        ActivityLog::record('product.created', $product, ['name' => $product->name]);
        return redirect()->route('admin.products.edit', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', ['product' => $product->load(['images', 'variants', 'categories']), 'categories' => Category::orderBy('name')->get(), 'brands' => Brand::orderBy('name')->get()]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);
        DB::transaction(fn () => $this->fill($product, $data, $request));
        ActivityLog::record('product.updated', $product, ['name' => $product->name]);
        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        if ($product->id && DB::table('order_items')->where('product_id', $product->id)->exists()) {
            $product->update(['status' => 'archived']);
            ActivityLog::record('product.archived', $product);
            return redirect()->route('admin.products.index')->with('success', 'This product has orders, so it was archived instead of deleted.');
        }
        foreach ($product->images as $img) { ImageUploader::delete($img->path); }
        ActivityLog::record('product.deleted', $product, ['name' => $product->name]);
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    public function destroyImage(Product $product, ProductImage $image)
    {
        abort_unless($image->product_id === $product->id, 404);
        ImageUploader::delete($image->path);
        $image->delete();
        return back()->with('success', 'Image removed.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $id = $product?->id;
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('products', 'slug')->ignore($id)],
            'sku' => ['nullable', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($id)],
            'type' => ['required', Rule::in(['simple', 'variable'])],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'short_description' => ['nullable', 'string', 'max:500'], 'description' => ['nullable', 'string', 'max:20000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock_qty' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'manage_stock' => ['nullable', 'boolean'], 'is_featured' => ['nullable', 'boolean'], 'is_new_arrival' => ['nullable', 'boolean'], 'tryon_enabled' => ['nullable', 'boolean'],
            'tryon_url' => ['nullable', 'url:https', 'max:500', function ($attr, $value, $fail) {
                // Only embed AR projects from trusted hosts (default: MyWebAR). Extend via Settings > Try-On allowed hosts.
                $host = strtolower((string) parse_url($value, PHP_URL_HOST));
                $allowed = array_filter(array_map('trim', explode(',', strtolower((string) setting('tryon_allowed_hosts', 'mywebar.com')))));
                foreach ($allowed as $h) {
                    if ($host === $h || str_ends_with($host, '.'.$h)) { return; }
                }
                $fail('Try-On URL must be on an allowed host ('.implode(', ', $allowed).').');
            }],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'category_ids' => ['nullable', 'array'], 'category_ids.*' => ['integer', 'exists:categories,id'],
            'attr' => ['nullable', 'array'], 'attr.*' => ['nullable', 'string', 'max:60'],
            'tags' => ['nullable', 'string', 'max:300'],
            'meta_title' => ['nullable', 'max:200'], 'meta_description' => ['nullable', 'max:300'],
            'new_images' => ['nullable', 'array', 'max:12'], 'new_images.*' => ImageUploader::rules(),
            'new_alt' => ['nullable', 'array'], 'new_alt.*' => ['nullable', 'string', 'max:200'],
            'image_alt' => ['nullable', 'array'], 'image_alt.*' => ['nullable', 'string', 'max:200'],
            'image_pos' => ['nullable', 'array'], 'image_pos.*' => ['nullable', 'integer', 'min:0', 'max:999'],
            'variants' => ['nullable', 'array', 'max:60'],
            'variants.*.id' => ['nullable', 'integer'], 'variants.*.label' => ['required_with:variants.*.price', 'nullable', 'string', 'max:120'],
            'variants.*.sku' => ['nullable', 'string', 'max:80'], 'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0'], 'variants.*.stock_qty' => ['nullable', 'integer', 'min:0'],
            'variants.*.is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function fill(Product $product, array $d, Request $request): void
    {
        $variable = $d['type'] === 'variable';
        $variants = collect($d['variants'] ?? [])->filter(fn ($v) => filled($v['label'] ?? null));
        if ($variable) {
            abort_if($variants->isEmpty(), 422, 'A variable product needs at least one variant.');
            foreach ($variants as $v) { abort_if(! isset($v['price']) || $v['price'] === '', 422, 'Every variant needs a price.'); }
        }
        $publishing = $d['status'] === 'published';
        $product->fill([
            'name' => $d['name'], 'slug' => Slug::unique(Product::class, ($d['slug'] ?? null) ?: $d['name'], $product->id),
            'sku' => ($d['sku'] ?? null) ?: null, 'type' => $d['type'], 'status' => $d['status'],
            'short_description' => $d['short_description'] ?? null, 'description' => $d['description'] ?? null,
            'price' => $variable ? $variants->min(fn ($v) => (float) $v['price']) : ($d['price'] ?? null),
            'sale_price' => $variable ? null : ($d['sale_price'] ?? null),
            'stock_qty' => $d['stock_qty'] ?? 0, 'manage_stock' => (bool) ($d['manage_stock'] ?? false),
            'is_featured' => $request->boolean('is_featured'), 'is_new_arrival' => $request->boolean('is_new_arrival'), 'tryon_enabled' => $request->boolean('tryon_enabled'), 'tryon_url' => $d['tryon_url'] ?? null,
            'brand_id' => $d['brand_id'] ?? null,
            'specs' => array_filter($d['attr'] ?? [], fn ($v) => filled($v)) ?: null,
            'tags' => array_values(array_filter(array_map('trim', explode(',', $d['tags'] ?? '')))) ?: null,
            'meta_title' => $d['meta_title'] ?? null, 'meta_description' => $d['meta_description'] ?? null,
        ]);
        if ($publishing && ! $product->published_at) { $product->published_at = now(); }
        $product->save();
        $product->categories()->sync($d['category_ids'] ?? []);

        // existing images: alt + position
        foreach ($product->images as $img) {
            $img->update(['alt' => $d['image_alt'][$img->id] ?? $img->alt, 'position' => $d['image_pos'][$img->id] ?? $img->position]);
        }
        foreach ($request->file('new_images', []) as $i => $file) {
            $product->images()->create(['path' => ImageUploader::store($file, 'products'), 'alt' => $d['new_alt'][$i] ?? $product->name, 'position' => $product->images()->count()]);
        }

        // variants
        if ($variable) {
            $keep = [];
            foreach ($variants as $v) {
                $attrs = ['label' => $v['label'], 'sku' => ($v['sku'] ?? null) ?: null, 'price' => $v['price'], 'sale_price' => ($v['sale_price'] ?? '') === '' ? null : $v['sale_price'],
                    'stock_qty' => $v['stock_qty'] ?? 0, 'is_active' => (bool) ($v['is_active'] ?? false)];
                $row = ! empty($v['id']) ? $product->variants()->whereKey($v['id'])->first() : null;
                $row ? $row->update($attrs) : ($row = $product->variants()->create($attrs));
                $keep[] = $row->id;
            }
            $product->variants()->whereNotIn('id', $keep)->delete();
        } else {
            $product->variants()->delete();
        }
    }
}
