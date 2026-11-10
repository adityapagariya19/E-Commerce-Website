<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\StoreException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Server-backed cart: guest carts are keyed by a random session token, member carts by user id. */
class CartService
{
    public function __construct(private PricingService $pricing)
    {
    }

    public function cart(bool $create = true): ?Cart
    {
        if ($user = Auth::user()) {
            return $create ? Cart::firstOrCreate(['user_id' => $user->id]) : Cart::where('user_id', $user->id)->first();
        }
        $token = session('cart_token');
        if (! $token && $create) {
            $token = Str::random(40);
            session(['cart_token' => $token]);
        }
        if (! $token) {
            return null;
        }
        return $create ? Cart::firstOrCreate(['token' => $token]) : Cart::where('token', $token)->first();
    }

    public function count(): int
    {
        $cart = $this->cart(false);
        return $cart ? (int) $cart->items()->sum('qty') : 0;
    }

    public function add(Product $product, ?int $variantId, int $qty): CartItem
    {
        $qty = max(1, $qty);
        $variant = $this->resolveVariant($product, $variantId);
        if (! $product->isPurchasable()) {
            throw new StoreException('Sorry, this product cannot be purchased right now.');
        }
        $cart = $this->cart();
        $item = $cart->items()->where('product_id', $product->id)->where('variant_id', $variant?->id)->first();
        $newQty = ($item?->qty ?? 0) + $qty;
        $this->assertStock($product, $variant, $newQty);

        if ($item) {
            $item->update(['qty' => $newQty]);
            return $item;
        }
        return $cart->items()->create(['product_id' => $product->id, 'variant_id' => $variant?->id, 'qty' => $qty]);
    }

    public function update(int $itemId, int $qty): void
    {
        $item = $this->cart()->items()->with(['product', 'variant'])->findOrFail($itemId);
        if ($qty <= 0) {
            $item->delete();
            return;
        }
        $this->assertStock($item->product, $item->variant, $qty);
        $item->update(['qty' => $qty]);
    }

    public function remove(int $itemId): void
    {
        $this->cart()->items()->whereKey($itemId)->delete();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->update(['coupon_code' => null]);
    }

    /** Lines with live product data + stock problems flagged. */
    public function lines(?Cart $cart = null): Collection
    {
        $cart ??= $this->cart(false);
        if (! $cart) {
            return collect();
        }
        return $cart->items()->with(['product.images', 'product.categories', 'product.variants', 'variant'])->get()
            ->map(function (CartItem $i) {
                $problem = null;
                if (! $i->product || $i->product->status !== 'published' || ! $i->product->isPurchasable()) {
                    $problem = 'No longer available';
                } elseif ($i->product->isVariable() && (! $i->variant || ! $i->variant->is_active)) {
                    $problem = 'Selected option no longer available';
                } elseif ($i->qty > $i->product->availableStock($i->variant)) {
                    $problem = 'Only '.$i->product->availableStock($i->variant).' left in stock';
                }
                return ['item' => $i, 'product' => $i->product, 'variant' => $i->variant, 'qty' => $i->qty, 'problem' => $problem];
            })->filter(fn ($l) => $l['product'])->values();
    }

    public function summary(?Cart $cart = null, ?User $user = null): array
    {
        $cart ??= $this->cart(false);
        $lines = $this->lines($cart);
        $quote = $this->pricing->quote($lines, $cart?->coupon_code, $user ?? Auth::user());
        if ($quote['coupon_error'] && $cart) {
            $cart->update(['coupon_code' => null]); // silently drop a coupon that became invalid
        }
        $quote['cart_lines'] = $lines;
        $quote['has_problems'] = $lines->contains(fn ($l) => $l['problem']);
        return $quote;
    }

    public function applyCoupon(string $code): void
    {
        $cart = $this->cart();
        $cart->update(['coupon_code' => strtoupper(trim($code))]);
        $quote = $this->pricing->quote($this->lines($cart), $cart->coupon_code, Auth::user());
        if ($quote['coupon_error']) {
            $cart->update(['coupon_code' => null]);
            throw new StoreException($quote['coupon_error']);
        }
    }

    public function removeCoupon(): void
    {
        $this->cart()->update(['coupon_code' => null]);
    }

    /** Called after login: fold the guest cart into the member's cart. */
    public function mergeGuestCart(User $user, ?string $guestToken): void
    {
        if (! $guestToken) {
            return;
        }
        $guest = Cart::where('token', $guestToken)->whereNull('user_id')->first();
        if (! $guest) {
            return;
        }
        DB::transaction(function () use ($guest, $user) {
            $mine = Cart::firstOrCreate(['user_id' => $user->id]);
            foreach ($guest->items()->with(['product', 'variant'])->get() as $gi) {
                $existing = $mine->items()->where('product_id', $gi->product_id)->where('variant_id', $gi->variant_id)->first();
                $want = ($existing?->qty ?? 0) + $gi->qty;
                $max = $gi->product ? $gi->product->availableStock($gi->variant) : 0;
                $qty = min($want, $max);
                if ($qty < 1) {
                    continue;
                }
                $existing ? $existing->update(['qty' => $qty]) : $mine->items()->create(['product_id' => $gi->product_id, 'variant_id' => $gi->variant_id, 'qty' => $qty]);
            }
            if (! $mine->coupon_code && $guest->coupon_code) {
                $mine->update(['coupon_code' => $guest->coupon_code]);
            }
            $guest->delete();
        });
        session()->forget('cart_token');
    }

    private function resolveVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        if (! $product->isVariable()) {
            return null;
        }
        $variant = $variantId ? $product->variants()->where('id', $variantId)->where('is_active', true)->first() : null;
        if (! $variant) {
            throw new StoreException('Please choose a product option before adding to cart.');
        }
        return $variant;
    }

    private function assertStock(Product $product, ?ProductVariant $variant, int $qty): void
    {
        $available = $product->availableStock($variant);
        if ($qty > $available) {
            throw new StoreException($available > 0 ? "Only {$available} left in stock." : 'Sorry, this item is out of stock.');
        }
    }
}
