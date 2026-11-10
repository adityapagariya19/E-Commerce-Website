<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The ONLY place prices and totals are calculated. Browser-submitted prices are never read.
 * All maths is done in integer paise to avoid float drift.
 */
class PricingService
{
    /**
     * @param Collection<int, array{product: \App\Models\Product, variant: ?\App\Models\ProductVariant, qty: int}> $lines
     */
    public function quote(Collection $lines, ?string $couponCode = null, ?User $user = null, ?string $email = null): array
    {
        $priced = [];
        $subtotal = 0;
        foreach ($lines as $line) {
            $p = $line['product']->pricing($line['variant'] ?? null)['current'];
            $unit = $p === null ? 0 : to_paise($p);
            $lineTotal = $unit * $line['qty'];
            $subtotal += $lineTotal;
            $priced[] = $line + ['unit_paise' => $unit, 'line_paise' => $lineTotal];
        }

        $coupon = null;
        $couponError = null;
        $discount = 0;
        if ($couponCode) {
            $coupon = Coupon::where('code', strtoupper(trim($couponCode)))->first();
            if (! $coupon) {
                $couponError = 'Coupon "'.e(strtoupper(trim($couponCode))).'" does not exist.';
            } elseif ($err = $coupon->check($subtotal, $user?->id, $email ?? $user?->email)) {
                $couponError = $err;
                $coupon = null;
            } else {
                $eligible = 0;
                foreach ($priced as $l) {
                    if ($coupon->appliesTo($l['product'])) {
                        $eligible += $l['line_paise'];
                    }
                }
                if ($eligible === 0) {
                    $couponError = 'This coupon does not apply to the items in your cart.';
                    $coupon = null;
                } else {
                    $discount = $coupon->type === 'percent'
                        ? (int) floor($eligible * ((float) $coupon->value) / 100)
                        : min(to_paise($coupon->value), $eligible);
                    if ($coupon->max_discount !== null) {
                        $discount = min($discount, to_paise($coupon->max_discount));
                    }
                    $discount = min($discount, $subtotal);
                }
            }
        }

        $shipping = 0;
        if ($subtotal > 0) {
            $flat = to_paise(setting('shipping_flat_rate', 0));
            $threshold = to_paise(setting('shipping_free_threshold', 0));
            $shipping = ($threshold > 0 && ($subtotal - $discount) >= $threshold) ? 0 : $flat;
        }

        $taxRate = (float) setting('tax_rate', 0);
        $tax = (int) round(($subtotal - $discount) * $taxRate / 100);
        $total = $subtotal - $discount + $shipping + $tax;

        return [
            'lines' => $priced,
            'coupon' => $coupon,
            'coupon_error' => $couponError,
            'subtotal_paise' => $subtotal, 'discount_paise' => $discount, 'shipping_paise' => $shipping,
            'tax_paise' => $tax, 'total_paise' => $total,
            'subtotal' => from_paise($subtotal), 'discount' => from_paise($discount), 'shipping' => from_paise($shipping),
            'tax' => from_paise($tax), 'total' => from_paise($total),
        ];
    }
}
