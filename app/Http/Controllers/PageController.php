<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    public function about()
    {
        return view('store.about', ['page' => Page::where('slug', 'about-us')->where('is_active', true)->first()]);
    }

    public function show(string $slug)
    {
        return view('store.page', ['page' => Page::where('slug', $slug)->where('is_active', true)->firstOrFail()]);
    }
}
