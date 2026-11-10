<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\HomeBlock;
use App\Support\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomeBlockController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.blocks.index', ['blocks' => HomeBlock::when($request->filled('section'), fn ($q) => $q->where('section', $request->query('section')))->orderBy('section')->orderBy('position')->get()]);
    }

    public function create(Request $request)
    {
        return view('admin.blocks.form', ['block' => new HomeBlock(['section' => $request->query('section', 'collage'), 'is_active' => true])]);
    }

    public function edit(HomeBlock $block) { return view('admin.blocks.form', compact('block')); }
    public function store(Request $r) { $this->save($r, new HomeBlock()); return redirect()->route('admin.blocks.index')->with('success', 'Block created.'); }
    public function update(Request $r, HomeBlock $block) { $this->save($r, $block); return redirect()->route('admin.blocks.index')->with('success', 'Block updated.'); }

    public function destroy(HomeBlock $block)
    {
        ImageUploader::delete($block->image);
        ActivityLog::record('block.deleted', $block);
        $block->delete();
        return back()->with('success', 'Block deleted.');
    }

    private function save(Request $request, HomeBlock $b): void
    {
        $d = $request->validate([
            'section' => ['required', Rule::in(array_keys(HomeBlock::SECTIONS))], 'eyebrow' => ['nullable', 'max:120'], 'title' => ['nullable', 'max:200'], 'subtitle' => ['nullable', 'max:500'],
            'alt' => ['nullable', 'max:200'], 'cta_label' => ['nullable', 'max:40'], 'url' => ['nullable', 'max:300', 'regex:/^(\/|https?:\/\/)/'],
            'icon' => ['nullable', Rule::in(['truck', 'support', 'money', 'shield'])], 'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'image' => ImageUploader::rules(),
        ]);
        $uploaded = $request->hasFile('image');
        $b->fill([
            'section' => $d['section'], 'eyebrow' => $d['eyebrow'] ?? null, 'title' => $d['title'] ?? null, 'subtitle' => $d['subtitle'] ?? null, 'alt' => $d['alt'] ?? null,
            'cta_label' => $d['cta_label'] ?? null, 'url' => $d['url'] ?? null, 'icon' => $d['icon'] ?? null, 'position' => $d['position'] ?? 0,
            'is_active' => $request->boolean('is_active'),
            'image' => ImageUploader::replace($request->file('image'), $b->image, 'blocks'),
            // owner confirmed the content once they edit/upload it, or ticks the box explicitly
            'is_placeholder' => $uploaded ? false : ($request->has('confirm_content') ? ! $request->boolean('confirm_content') : $b->is_placeholder),
        ])->save();
        ActivityLog::record($b->wasRecentlyCreated ? 'block.created' : 'block.updated', $b, ['section' => $b->section]);
    }
}
