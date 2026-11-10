<?php

namespace App\Http\Controllers;

use App\Models\HeroSlide;
use App\Models\HomeBlock;
use App\Models\Product;
use App\Services\PopularProductsService;

class HomeController extends Controller
{
    public function index(PopularProductsService $popular)
    {
        // New Arrivals rule: products the admin flagged "New Arrival" (newest first); if none are flagged, the most recently published products.
        $limit = config('jbo.new_arrivals_limit');
        $new = Product::published()->with(['images', 'variants'])->where('is_new_arrival', true)->orderByDesc('published_at')->orderByDesc('id')->limit($limit)->get();
        if ($new->isEmpty()) {
            $new = Product::published()->with(['images', 'variants'])->orderByDesc('published_at')->orderByDesc('id')->limit($limit)->get();
        }

        return view('store.home', [
            'slides' => HeroSlide::active()->get(),
            'collage' => HomeBlock::section('collage')->get(),
            'promo' => HomeBlock::section('promo')->first(),
            'trust' => HomeBlock::section('trust')->get(),
            'social' => HomeBlock::section('social')->get(),
            'newArrivals' => $new,
            'popular' => $popular->get(),
        ]);
    }
}
