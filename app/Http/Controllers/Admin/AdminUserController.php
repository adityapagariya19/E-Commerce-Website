<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    public function index() { return view('admin.users.index', ['users' => User::where('role', '!=', 'customer')->orderBy('name')->get()]); }
    public function create() { return view('admin.users.form', ['user' => new User(['is_active' => true, 'role' => 'super_admin'])]); }
    public function edit(User $user) { abort_if($user->role === 'customer', 404); return view('admin.users.form', compact('user')); }

    public function store(Request $request)
    {
        $d = $request->validate([
            'name' => ['required', 'max:100'], 'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(config('permissions.roles')))], 'password' => ['required', Password::min(10)->letters()->numbers()],
        ]);
        $u = User::create($d + ['is_active' => $request->boolean('is_active', true)]);
        ActivityLog::record('admin_user.created', $u, ['role' => $u->role]);
        return redirect()->route('admin.users.index')->with('success', 'Admin user created.');
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->role === 'customer', 404);
        $d = $request->validate([
            'name' => ['required', 'max:100'], 'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(config('permissions.roles')))], 'password' => ['nullable', Password::min(10)->letters()->numbers()],
        ]);
        $active = $request->boolean('is_active');
        if ($user->id === $request->user()->id && (! $active || $d['role'] !== $user->role)) {
            return back()->with('error', 'You cannot deactivate or change the role of your own account.');
        }
        if ($user->role === 'super_admin' && ($d['role'] !== 'super_admin' || ! $active) && User::where('role', 'super_admin')->where('is_active', true)->where('id', '!=', $user->id)->doesntExist()) {
            return back()->with('error', 'At least one active admin is required.');
        }
        $user->fill(['name' => $d['name'], 'email' => $d['email'], 'role' => $d['role'], 'is_active' => $active]);
        if (! empty($d['password'])) { $user->password = $d['password']; }
        $user->save();
        ActivityLog::record('admin_user.updated', $user, ['role' => $user->role]);
        return redirect()->route('admin.users.index')->with('success', 'Admin user updated.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->role === 'customer', 404);
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->role === 'super_admin' && User::where('role', 'super_admin')->where('id', '!=', $user->id)->doesntExist()) {
            return back()->with('error', 'At least one admin is required.');
        }
        ActivityLog::record('admin_user.deleted', $user, ['email' => $user->email]);
        $user->delete();
        return back()->with('success', 'Admin user deleted.');
    }
}
