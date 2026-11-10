<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    private const PER_PAGE = 6; // reference: "Showing 1–6 of 33 results"

    public function index(Request $request)
    {
        return $this->render($request, null);
    }

    public function category(Request $request, Category $category)
    {
        abort_unless($category->is_active, 404);
        return $this->render($request, $category);
    }

    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }
        $like = '%'.addcslashes($q, '%_\\').'%';
        return response()->json(Product::published()->with('images')->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like))
            ->orderBy('name')->limit(6)->get()->map(fn ($p) => ['name' => $p->name, 'url' => route('product', $p), 'image' => $p->image_url]));
    }

    private function render(Request $request, ?Category $category)
    {
        $effective = 'CASE WHEN products.sale_price IS NOT NULL AND products.sale_price > 0 AND products.sale_price < products.price THEN products.sale_price ELSE products.price END';
        $counted = "'".implode("','", config('jbo.popular.counted_statuses'))."'";

        $q = Product::published()->with(['images', 'variants']);
        if ($category) {
            $q->whereHas('categories', fn ($w) => $w->whereIn('categories.id', $category->descendantIds()));
        } elseif ($slug = $request->query('category')) {
            if ($c = Category::where('slug', $slug)->first()) {
                $q->whereHas('categories', fn ($w) => $w->whereIn('categories.id', $c->descendantIds()));
            }
        }
        if ($s = trim((string) $request->query('q', $request->query('s', '')))) {
            $like = '%'.addcslashes($s, '%_\\').'%';
            $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('short_description', 'like', $like)
                ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like)));
        }
        if (is_numeric($request->query('min_price'))) {
            $q->whereRaw("($effective) >= ?", [$this->num($request->query('min_price'))]);
        }
        if (is_numeric($request->query('max_price'))) {
            $q->whereRaw("($effective) <= ?", [$this->num($request->query('max_price'))]);
        }
        foreach (['gender', 'frame_shape', 'frame_color'] as $attr) {
            if ($v = $request->query($attr)) {
                $q->where("specs->{$attr}", $v);
            }
        }
        if ($tag = $request->query('tag')) {
            $q->whereJsonContains('tags', $tag);
        }

        $orderby = $request->query('orderby', 'default');
        match ($orderby) {
            'price' => $q->orderByRaw("($effective) asc"),
            'price-desc' => $q->orderByRaw("($effective) desc"),
            'date' => $q->orderByDesc('published_at')->orderByDesc('id'),
            'rating' => $q->addSelect(['avg_rating' => DB::table('reviews')->selectRaw('COALESCE(AVG(rating),0)')->whereColumn('reviews.product_id', 'products.id')->where('status', 'approved')])->orderByDesc('avg_rating'),
            'popularity' => $q->addSelect(['units_sold' => DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->selectRaw('COALESCE(SUM(oi.qty - oi.refunded_qty),0)')->whereColumn('oi.product_id', 'products.id')->whereRaw("o.status in ($counted)")])->orderByDesc('units_sold'),
            default => $q->orderByDesc('is_featured')->orderByDesc('published_at')->orderByDesc('id'),
        };
        $q->addSelect('products.*');

        $products = $q->paginate(self::PER_PAGE)->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('store.partials.product-grid', ['products' => $products])->render(),
                'next' => $products->nextPageUrl(),
                'showing' => $this->showing($products, true),
            ]);
        }

        $published = Product::published();
        $categories = Category::active()->whereNull('parent_id')->with(['children' => fn ($c) => $c->where('is_active', true)])->orderBy('position')->orderBy('name')->get();
        $counts = DB::table('category_product as cp')->join('products as p', 'p.id', '=', 'cp.product_id')->where('p.status', 'published')
            ->selectRaw('cp.category_id, COUNT(*) as c')->groupBy('cp.category_id')->pluck('c', 'category_id');
        $bounds = (clone $published)->selectRaw("MIN($effective) as lo, MAX($effective) as hi")->first();

        $attrOptions = [];
        foreach ((clone $published)->pluck('specs') as $a) {
            foreach (['gender', 'frame_shape', 'frame_color'] as $k) {
                if (! empty($a[$k])) $attrOptions[$k][$a[$k]] = true;
            }
        }
        $allTags = (clone $published)->pluck('tags')->flatten()->filter()->unique()->sort()->values();

        return view('store.shop', [
            'products' => $products, 'category' => $category, 'categories' => $categories, 'counts' => $counts,
            'priceMin' => (int) floor($bounds->lo ?? 0), 'priceMax' => (int) ceil($bounds->hi ?? 0),
            'attrOptions' => collect($attrOptions)->map(fn ($v) => collect(array_keys($v))->sort()->values()),
            'tags' => $allTags, 'orderby' => $orderby, 'showing' => $this->showing($products),
        ]);
    }

    /** Whole numbers are bound as integers: PDO sends floats as strings, which SQLite compares incorrectly. */
    private function num(mixed $v): int|float
    {
        $n = (float) $v;
        return floor($n) == $n ? (int) $n : $n;
    }

    private function showing($p, bool $fromStart = false): string
    {
        if ($p->total() === 0) {
            return 'No products were found matching your selection.';
        }
        if ($p->total() <= $p->perPage()) {
            return 'Showing all '.$p->total().' results';
        }
        $start = $fromStart ? 1 : $p->firstItem();
        return 'Showing '.$start.'–'.$p->lastItem().' of '.$p->total().' results';
    }
}
