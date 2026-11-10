<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeCustomer;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class CustomerAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    /** Only same-site relative paths are accepted as a post-login destination (blocks open redirects). */
    private function safeTarget(Request $request): ?string
    {
        $to = $request->input('redirect');
        if (is_string($to) && str_starts_with($to, '/') && ! str_starts_with($to, '//') && ! str_contains($to, '\\')
            && ! preg_match('#^/(login|register|forgot-password|reset-password|logout)#', $to)) {
            return $to;
        }
        return null;
    }

    public function login(Request $request, CartService $cart)
    {
        $d = $request->validate(['username' => ['required', 'string', 'max:150'], 'password' => ['required', 'string']]);
        $guestToken = $request->session()->get('cart_token');

        if (! Auth::attempt(['email' => $d['username'], 'password' => $d['password'], 'is_active' => true], $request->boolean('remember'))) {
            return back()->withInput($request->only('username', 'redirect'))->with('login_error', 'Invalid username/email or password.');
        }
        $request->session()->regenerate();
        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->save();
        $cart->mergeGuestCart($user, $guestToken);

        $target = $this->safeTarget($request);
        return $target ? redirect($target) : redirect()->intended(route('account.dashboard'));
    }

    /** Full registration: name, email, password + confirmation. The customer is signed in straight away. */
    public function register(Request $request, CartService $cart)
    {
        $d = $request->validateWithBag('register', [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'website' => ['nullable', 'max:0'], // honeypot
        ], ['email.unique' => 'An account is already registered with this email address. Please log in.']);

        $guestToken = $request->session()->get('cart_token');
        $user = User::create(['name' => $d['name'], 'email' => strtolower($d['email']), 'password' => $d['password'], 'role' => 'customer', 'is_active' => true]);
        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $cart->mergeGuestCart($user, $guestToken);

        try {
            Mail::to($user->email)->send(new WelcomeCustomer($user)); // failure must never block registration
        } catch (\Throwable $e) {
            logger()->warning('Welcome email failed: '.$e->getMessage());
        }
        $target = $this->safeTarget($request);
        return ($target ? redirect($target) : redirect()->route('account.dashboard'))->with('success', 'Your account has been created.');
    }

    public function logout(Request $request)
    {
        $wasAdmin = $request->user()?->isAdmin();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
