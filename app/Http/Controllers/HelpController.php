<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\FaqTopic;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function show()
    {
        return view('store.help', ['topics' => FaqTopic::where('is_active', true)->orderBy('position')->with(['faqs' => fn ($q) => $q->where('is_active', true)])->get()]);
    }

    public function feedback(Request $request)
    {
        $d = $request->validate(['name' => ['required', 'max:100'], 'email' => ['required', 'email', 'max:150'], 'message' => ['required', 'min:3', 'max:3000'], 'website' => ['nullable', 'max:0']]);
        $m = ContactMessage::create($d + ['source' => 'help_feedback', 'ip' => $request->ip()]);
        ContactController::notify($m);
        return redirect()->to(route('help').'#feedback')->with('success', 'Thanks for your feedback!');
    }
}
