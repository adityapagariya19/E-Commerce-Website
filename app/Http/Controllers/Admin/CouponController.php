<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index() { return view('admin.coupons.index', ['coupons' => Coupon::withCount('usages')->latest()->get()]); }
    public function create() { return view('admin.coupons.form', ['coupon' => new Coupon(['type' => 'percent', 'is_active' => true]), 'products' => Product::orderBy('name')->get(['id', 'name']), 'categories' => Category::orderBy('name')->get(['id', 'name'])]); }
    public function edit(Coupon $coupon) { return view('admin.coupons.form', ['coupon' => $coupon, 'products' => Product::orderBy('name')->get(['id', 'name']), 'categories' => Category::orderBy('name')->get(['id', 'name'])]); }
    public function store(Request $r) { $this->save($r, new Coupon()); return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.'); }
    public function update(Request $r, Coupon $coupon) { $this->save($r, $coupon); return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated.'); }

    public function destroy(Coupon $coupon)
    {
        ActivityLog::record('coupon.deleted', $coupon, ['code' => $coupon->code]);
        $coupon->delete();
        return back()->with('success', 'Coupon deleted.');
    }

    private function save(Request $request, Coupon $c): void
    {
        $d = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:50', Rule::unique('coupons', 'code')->ignore($c->id)],
            'description' => ['nullable', 'max:200'], 'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'gt:0', $request->input('type') === 'percent' ? 'max:100' : 'max:1000000'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'], 'max_discount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'], 'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'product_ids' => ['nullable', 'array'], 'product_ids.*' => ['integer', 'exists:products,id'],
            'category_ids' => ['nullable', 'array'], 'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);
        $c->fill([
            'code' => $d['code'], 'description' => $d['description'] ?? null, 'type' => $d['type'], 'value' => $d['value'],
            'min_subtotal' => $d['min_subtotal'] ?? null, 'max_discount' => $d['max_discount'] ?? null,
            'starts_at' => $d['starts_at'] ?? null, 'ends_at' => $d['ends_at'] ?? null,
            'usage_limit' => $d['usage_limit'] ?? null, 'per_user_limit' => $d['per_user_limit'] ?? null,
            'product_ids' => array_map('intval', $d['product_ids'] ?? []) ?: null, 'category_ids' => array_map('intval', $d['category_ids'] ?? []) ?: null,
            'is_active' => $request->boolean('is_active'),
        ])->save();
        ActivityLog::record($c->wasRecentlyCreated ? 'coupon.created' : 'coupon.updated', $c, ['code' => $c->code]);
    }
}
