<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\HeroSlide;
use App\Support\ImageUploader;
use Illuminate\Http\Request;

class HeroSlideController extends Controller
{
    public function index() { return view('admin.hero.index', ['slides' => HeroSlide::orderBy('position')->orderBy('id')->get()]); }
    public function create() { return view('admin.hero.form', ['slide' => new HeroSlide(['is_active' => true, 'position' => HeroSlide::count()])]); }
    public function edit(HeroSlide $hero_slide) { return view('admin.hero.form', ['slide' => $hero_slide]); }
    public function store(Request $r) { $this->save($r, new HeroSlide()); return redirect()->route('admin.hero-slides.index')->with('success', 'Slide created.'); }
    public function update(Request $r, HeroSlide $hero_slide) { $this->save($r, $hero_slide); return redirect()->route('admin.hero-slides.index')->with('success', 'Slide updated.'); }

    public function destroy(HeroSlide $hero_slide)
    {
        ImageUploader::delete($hero_slide->image);
        ImageUploader::delete($hero_slide->mobile_image);
        ActivityLog::record('hero.deleted', $hero_slide);
        $hero_slide->delete();
        return back()->with('success', 'Slide deleted.');
    }

    private function save(Request $request, HeroSlide $s): void
    {
        $d = $request->validate([
            'eyebrow' => ['nullable', 'max:120'], 'kicker' => ['nullable', 'max:120'], 'heading' => ['required', 'max:160'], 'subheading' => ['nullable', 'max:200'],
            'alt' => ['nullable', 'max:200'], 'cta_label' => ['nullable', 'max:40'],
            // internal path or http(s) URL only: blocks javascript: URLs
            'cta_url' => ['nullable', 'max:300', 'regex:/^(\/|https?:\/\/)/'], 'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'image' => ImageUploader::rules(! $s->exists), 'mobile_image' => ImageUploader::rules(),
        ]);
        $s->fill([
            'eyebrow' => $d['eyebrow'] ?? null, 'kicker' => $d['kicker'] ?? null, 'heading' => $d['heading'], 'subheading' => $d['subheading'] ?? null, 'alt' => $d['alt'] ?? null,
            'cta_label' => $d['cta_label'] ?? null, 'cta_url' => $d['cta_url'] ?? null, 'position' => $d['position'] ?? 0, 'is_active' => $request->boolean('is_active'),
            'image' => ImageUploader::replace($request->file('image'), $s->image, 'hero') ?? $s->image,
            'mobile_image' => ImageUploader::replace($request->file('mobile_image'), $s->mobile_image, 'hero'),
        ])->save();
        ActivityLog::record($s->wasRecentlyCreated ? 'hero.created' : 'hero.updated', $s);
    }
}
