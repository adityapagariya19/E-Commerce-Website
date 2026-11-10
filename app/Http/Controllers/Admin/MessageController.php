<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.messages.index', ['messages' => ContactMessage::when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->latest()->paginate(25)->withQueryString()]);
    }

    public function status(Request $request, ContactMessage $message)
    {
        $message->update($request->validate(['status' => ['required', Rule::in(['new', 'read', 'replied'])]]));
        return back()->with('success', 'Message marked '.$message->status.'.');
    }
}
