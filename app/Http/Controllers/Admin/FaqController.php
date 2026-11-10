<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\FaqTopic;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.faq.index', ['faqs' => Faq::with('topic')->when($request->filled('topic'), fn ($q) => $q->where('faq_topic_id', $request->query('topic')))->orderBy('faq_topic_id')->orderBy('position')->get(), 'topics' => FaqTopic::orderBy('position')->get()]);
    }
    public function create() { return view('admin.faq.form', ['faq' => new Faq(['is_active' => true]), 'topics' => FaqTopic::orderBy('position')->get()]); }
    public function edit(Faq $faq) { return view('admin.faq.form', ['faq' => $faq, 'topics' => FaqTopic::orderBy('position')->get()]); }
    public function store(Request $r) { $this->save($r, new Faq()); return redirect()->route('admin.faqs.index')->with('success', 'Question saved.'); }
    public function update(Request $r, Faq $faq) { $this->save($r, $faq); return redirect()->route('admin.faqs.index')->with('success', 'Question saved.'); }
    public function destroy(Faq $faq) { $faq->delete(); return back()->with('success', 'Question deleted.'); }

    private function save(Request $request, Faq $f): void
    {
        $d = $request->validate(['faq_topic_id' => ['required', 'exists:faq_topics,id'], 'question' => ['required', 'max:250'], 'answer' => ['nullable', 'max:5000'], 'position' => ['nullable', 'integer', 'min:0', 'max:999']]);
        $f->fill(['faq_topic_id' => $d['faq_topic_id'], 'question' => $d['question'], 'answer' => $d['answer'] ?? null, 'position' => $d['position'] ?? 0, 'is_active' => $request->boolean('is_active')])->save();
    }
}
