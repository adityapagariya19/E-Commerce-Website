<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaqTopic;
use Illuminate\Http\Request;

class FaqTopicController extends Controller
{
    public function index() { return view('admin.faq.topics', ['topics' => FaqTopic::withCount('faqs')->orderBy('position')->get()]); }
    public function create() { return view('admin.faq.topic-form', ['topic' => new FaqTopic(['is_active' => true])]); }
    public function edit(FaqTopic $faq_topic) { return view('admin.faq.topic-form', ['topic' => $faq_topic]); }
    public function store(Request $r) { $this->save($r, new FaqTopic()); return redirect()->route('admin.faq-topics.index')->with('success', 'Topic saved.'); }
    public function update(Request $r, FaqTopic $faq_topic) { $this->save($r, $faq_topic); return redirect()->route('admin.faq-topics.index')->with('success', 'Topic saved.'); }
    public function destroy(FaqTopic $faq_topic) { $faq_topic->delete(); return back()->with('success', 'Topic and its questions deleted.'); }

    private function save(Request $request, FaqTopic $t): void
    {
        $d = $request->validate(['title' => ['required', 'max:120'], 'position' => ['nullable', 'integer', 'min:0', 'max:999']]);
        $t->fill(['title' => $d['title'], 'position' => $d['position'] ?? 0, 'is_active' => $request->boolean('is_active')])->save();
    }
}
