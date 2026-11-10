<?php

namespace App\Http\Controllers;

use App\Models\Product;

/**
 * UNVERIFIED ASSUMPTION: the reference only shows frame rows with "Try Now" / "Buy Now". The webcam overlay behaviour is our interpretation.
 */
class TryOnController extends Controller
{
    public function index()
    {
        return view('store.tryon', [
            'products' => Product::published()->with(['images', 'variants'])->where('tryon_enabled', true)->orderBy('name')->get(),
        ]);
    }
}
