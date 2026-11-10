<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function show()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $d = $request->validate(['email' => ['required', 'string'], 'password' => ['required', 'string']]);
        $loginField = filter_var($d['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        if (! Auth::attempt([$loginField => $d['email'], 'password' => $d['password'], 'is_active' => true], $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'These credentials do not match our records.']);
        }
        if (! Auth::user()->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            return back()->withErrors(['email' => 'This account does not have admin access.']);
        }
        $request->session()->regenerate();
        Auth::user()->forceFill(['last_login_at' => now()])->save();
        ActivityLog::record('admin.login');
        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        ActivityLog::record('admin.logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
