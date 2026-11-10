<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(Product $product)
    {
        abort_unless($product->status === 'published', 404);
        $product->load(['images', 'variants' => fn ($q) => $q->where('is_active', true), 'categories', 'brand', 'approvedReviews']);

        $catIds = $product->categories->pluck('id');
        $related = Product::published()->with(['images', 'variants'])->where('id', '!=', $product->id)
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $catIds))->inRandomOrder()->limit(4)->get();
        $prev = Product::published()->where('id', '<', $product->id)->orderByDesc('id')->first();
        $next = Product::published()->where('id', '>', $product->id)->orderBy('id')->first();

        return view('store.product', compact('product', 'related', 'prev', 'next'));
    }

    public function review(Request $request, Product $product)
    {
        abort_unless($product->status === 'published', 404);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'max:2000'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'website' => ['nullable', 'max:0'], // honeypot
        ], ['rating.required' => 'Please select a rating']);

        Review::create([
            'product_id' => $product->id, 'user_id' => $request->user()?->id, 'name' => $data['name'],
            'email' => $data['email'], 'rating' => $data['rating'], 'body' => strip_tags($data['body']), 'status' => 'pending',
        ]);
        return back()->with('success', 'Your review is awaiting approval');
    }
}
