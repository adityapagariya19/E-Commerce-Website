<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Page;
use App\Support\Slug;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index() { return view('admin.pages.index', ['pages' => Page::orderBy('title')->get()]); }
    public function create() { return view('admin.pages.form', ['page' => new Page(['is_active' => true])]); }
    public function edit(Page $page) { return view('admin.pages.form', compact('page')); }
    public function store(Request $r) { $this->save($r, new Page()); return redirect()->route('admin.pages.index')->with('success', 'Page created.'); }
    public function update(Request $r, Page $page) { $this->save($r, $page); return redirect()->route('admin.pages.index')->with('success', 'Page updated.'); }

    public function destroy(Page $page)
    {
        abort_if($page->slug === 'about-us', 422, 'The About Us page cannot be deleted.');
        ActivityLog::record('page.deleted', $page);
        $page->delete();
        return back()->with('success', 'Page deleted.');
    }

    private function save(Request $request, Page $p): void
    {
        $d = $request->validate(['title' => ['required', 'max:150'], 'slug' => ['nullable', 'max:150', Rule::unique('pages', 'slug')->ignore($p->id)], 'body' => ['nullable', 'max:50000']]);
        // Body is Markdown, rendered with raw HTML stripped (see views) — admins cannot inject scripts.
        $p->fill(['title' => $d['title'], 'slug' => $p->exists && $p->slug === 'about-us' ? 'about-us' : Slug::unique(Page::class, ($d['slug'] ?? null) ?: $d['title'], $p->id), 'body' => $d['body'] ?? null, 'is_active' => $request->boolean('is_active')])->save();
        ActivityLog::record($p->wasRecentlyCreated ? 'page.created' : 'page.updated', $p, ['slug' => $p->slug]);
    }
}
