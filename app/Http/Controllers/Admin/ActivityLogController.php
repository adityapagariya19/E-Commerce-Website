<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.activity', ['logs' => ActivityLog::with('user')->when($request->filled('q'), fn ($q) => $q->where('action', 'like', '%'.addcslashes($request->query('q'), '%_\\').'%'))->latest('id')->paginate(40)->withQueryString()]);
    }
}
