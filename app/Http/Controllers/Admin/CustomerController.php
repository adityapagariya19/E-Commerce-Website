<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $users = User::where('role', 'customer')->withCount('orders')->withSum('orders as spent', 'total')
            ->when($request->filled('q'), function ($w) use ($request) {
                $like = '%'.addcslashes($request->query('q'), '%_\\').'%';
                $w->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like));
            })
            ->when($request->query('status') === 'inactive', fn ($w) => $w->where('is_active', false))
            ->latest()->paginate(20)->withQueryString();
        return view('admin.customers.index', compact('users'));
    }

    public function show(User $user)
    {
        abort_unless($user->role === 'customer', 404);
        return view('admin.customers.show', ['user' => $user->load('addresses'), 'orders' => $user->orders()->latest('placed_at')->get()]);
    }

    public function toggle(User $user)
    {
        abort_unless($user->role === 'customer', 404);
        $user->update(['is_active' => ! $user->is_active]);
        ActivityLog::record($user->is_active ? 'customer.enabled' : 'customer.disabled', $user);
        return back()->with('success', 'Account '.($user->is_active ? 'enabled' : 'disabled').'.');
    }
}
