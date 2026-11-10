<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.reviews.index', ['reviews' => Review::with('product')->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->latest()->paginate(25)->withQueryString()]);
    }

    public function update(Request $request, Review $review)
    {
        $review->update($request->validate(['status' => ['required', Rule::in(['pending', 'approved', 'rejected'])]]));
        return back()->with('success', 'Review '.$review->status.'.');
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return back()->with('success', 'Review deleted.');
    }
}
