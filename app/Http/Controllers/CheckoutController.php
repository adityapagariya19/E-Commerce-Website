<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\RazorpayService;
use App\Support\StoreException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public const STATES = ['Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala',
        'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
        'Uttar Pradesh', 'Uttarakhand', 'West Bengal', 'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Jammu and Kashmir',
        'Ladakh', 'Lakshadweep', 'Puducherry'];

    public function __construct(private CartService $cart, private CheckoutService $checkout, private RazorpayService $razorpay)
    {
    }

    public function show(Request $request)
    {
        $summary = $this->cart->summary();
        if ($summary['cart_lines']->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Your cart is empty.');
        }
        if ($summary['has_problems']) {
            return redirect()->route('cart')->with('error', 'Please fix the highlighted items before checkout.');
        }
        $request->session()->put('checkout_token', $request->session()->get('checkout_token', (string) Str::uuid()));
        $user = Auth::user();
        $default = $user?->addresses()->where('type', 'shipping')->latest('id')->first();

        return view('store.checkout', [
            'summary' => $summary, 'states' => self::STATES, 'default' => $default,
            'token' => $request->session()->get('checkout_token'), 'razorpayReady' => $this->razorpay->configured(),
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'email' => ['required', 'email', 'max:150'],
            'first_name' => ['required', 'string', 'max:60'], 'last_name' => ['required', 'string', 'max:60'],
            'company' => ['nullable', 'string', 'max:100'],
            'country' => ['required', Rule::in(['IN'])],
            'address_1' => ['required', 'string', 'max:150'], 'address_2' => ['nullable', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:80'], 'state' => ['required', Rule::in(self::STATES)],
            'postcode' => ['required', 'regex:/^[1-9][0-9]{5}$/'], 'phone' => ['required', 'regex:/^[0-9+\-\s]{10,15}$/'],
            'same_billing' => ['nullable', 'boolean'],
            'payment_method' => ['required', Rule::in(['cod', 'razorpay'])],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'terms' => ['accepted'],
            'create_account' => ['nullable', 'boolean'],
            'checkout_token' => ['required', 'string', 'max:64'],
        ];
        if (! $request->boolean('same_billing', true)) {
            $rules += [
                'b_first_name' => ['required', 'string', 'max:60'], 'b_last_name' => ['required', 'string', 'max:60'],
                'b_address_1' => ['required', 'string', 'max:150'], 'b_city' => ['required', 'string', 'max:80'],
                'b_state' => ['required', Rule::in(self::STATES)], 'b_postcode' => ['required', 'regex:/^[1-9][0-9]{5}$/'],
            ];
        }
        $v = $request->validate($rules, ['terms.accepted' => 'Please accept the Terms and Conditions and Privacy Policy to continue.', 'postcode.regex' => 'Enter a valid 6-digit PIN code.']);

        $shipping = ['first_name' => $v['first_name'], 'last_name' => $v['last_name'], 'company' => $v['company'] ?? null, 'line1' => $v['address_1'],
            'line2' => $v['address_2'] ?? null, 'city' => $v['city'], 'state' => $v['state'], 'postcode' => $v['postcode'], 'country' => 'IN', 'phone' => $v['phone']];
        $billing = $request->boolean('same_billing', true) ? $shipping + ['email' => $v['email']] : [
            'first_name' => $v['b_first_name'], 'last_name' => $v['b_last_name'], 'company' => null, 'line1' => $v['b_address_1'], 'line2' => $request->input('b_address_2'),
            'city' => $v['b_city'], 'state' => $v['b_state'], 'postcode' => $v['b_postcode'], 'country' => 'IN', 'phone' => $v['phone'], 'email' => $v['email'],
        ];

        $user = Auth::user();
        try {
            $order = $this->checkout->place([
                'email' => $v['email'], 'phone' => $v['phone'], 'billing' => $billing, 'shipping' => $shipping,
                'payment_method' => $v['payment_method'], 'customer_note' => $v['customer_note'] ?? null, 'checkout_token' => $v['checkout_token'],
            ], $user);
        } catch (StoreException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Optional account creation for guests (WooCommerce-style): password link is emailed.
        if (! $user && $request->boolean('create_account') && ! User::where('email', $v['email'])->exists()) {
            $new = User::create(['name' => $v['first_name'].' '.$v['last_name'], 'email' => $v['email'], 'phone' => $v['phone'], 'password' => Hash::make(Str::random(40))]);
            $order->update(['user_id' => $new->id]);
            try { Password::sendResetLink(['email' => $new->email]); } catch (\Throwable $e) { logger()->warning('Reset mail failed'); }
        }
        $request->session()->forget('checkout_token');

        return $order->payment_method === 'razorpay'
            ? redirect()->route('checkout.pay', ['order' => $order->id, 'key' => $order->order_key])
            : redirect()->route('order.received', ['order' => $order->id, 'key' => $order->order_key]);
    }
}
