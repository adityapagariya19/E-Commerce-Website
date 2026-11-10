<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use App\Support\StoreException;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart)
    {
    }

    public function index()
    {
        return view('store.cart', ['summary' => $this->cart->summary()]);
    }

    public function drawer()
    {
        return response()->json(['html' => view('store.partials.cart-drawer', ['summary' => $this->cart->summary()])->render(), 'count' => $this->cart->count()]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);
        $product = Product::published()->with('variants')->find($data['product_id']);
        try {
            if (! $product) {
                throw new StoreException('This product is not available.');
            }
            $this->cart->add($product, $data['variant_id'] ?? null, (int) ($data['qty'] ?? 1));
        } catch (StoreException $e) {
            return $request->expectsJson() ? response()->json(['ok' => false, 'message' => $e->getMessage()], 422) : back()->with('error', $e->getMessage());
        }
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => '“'.$product->name.'” has been added to your cart.', 'count' => $this->cart->count(),
                'html' => view('store.partials.cart-drawer', ['summary' => $this->cart->summary()])->render()]);
        }
        return $request->boolean('buy_now') ? redirect()->route('checkout') : back()->with('success', '“'.$product->name.'” has been added to your cart.');
    }

    public function update(Request $request, int $item)
    {
        $qty = (int) $request->validate(['qty' => ['required', 'integer', 'min:0', 'max:99']])['qty'];
        try {
            $this->cart->update($item, $qty);
        } catch (StoreException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back();
    }

    public function remove(int $item)
    {
        $this->cart->remove($item);
        return back()->with('success', 'Item removed.');
    }

    public function coupon(Request $request)
    {
        $code = $request->validate(['coupon_code' => ['required', 'string', 'max:50']])['coupon_code'];
        try {
            $this->cart->applyCoupon($code);
        } catch (StoreException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Coupon code applied successfully.');
    }

    public function removeCoupon()
    {
        $this->cart->removeCoupon();
        return back()->with('success', 'Coupon removed.');
    }
}
