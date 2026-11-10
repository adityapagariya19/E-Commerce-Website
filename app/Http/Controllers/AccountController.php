<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\StoreException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    private function myOrder(Request $request, Order $order): Order
    {
        abort_unless($order->user_id === $request->user()->id, 404); // customers can never see other people's orders
        return $order;
    }

    public function dashboard(Request $request)
    {
        return view('account.dashboard', ['recent' => $request->user()->orders()->latest('placed_at')->limit(3)->get()]);
    }

    public function orders(Request $request)
    {
        return view('account.orders', ['orders' => $request->user()->orders()->withCount('items')->latest('placed_at')->paginate(10)]);
    }

    public function order(Request $request, Order $order)
    {
        return view('account.order', ['order' => $this->myOrder($request, $order)->load('items', 'histories')]);
    }

    public function cancel(Request $request, Order $order, OrderService $orders)
    {
        $order = $this->myOrder($request, $order);
        try {
            if (! $order->customerCanCancel()) {
                throw new StoreException('This order can no longer be cancelled online. Please contact us for help.');
            }
            $orders->transition($order, 'cancelled', 'Cancelled by customer.', null, true);
        } catch (StoreException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Order cancelled.');
    }

    /** Printable receipt (browser print / save as PDF). */
    public function receipt(Request $request, Order $order)
    {
        return view('account.receipt', ['order' => $this->myOrder($request, $order)->load('items')]);
    }

    public function downloads()
    {
        return view('account.downloads');
    }

    public function addresses(Request $request)
    {
        return view('account.addresses', ['addresses' => $request->user()->addresses()->get(), 'states' => CheckoutController::STATES]);
    }

    public function saveAddress(Request $request, ?Address $address = null)
    {
        if ($address) {
            abort_unless($address->user_id === $request->user()->id, 404);
        }
        $d = $request->validate([
            'type' => ['required', Rule::in(['billing', 'shipping'])],
            'first_name' => ['required', 'max:60'], 'last_name' => ['required', 'max:60'], 'company' => ['nullable', 'max:100'],
            'line1' => ['required', 'max:150'], 'line2' => ['nullable', 'max:150'], 'city' => ['required', 'max:80'],
            'state' => ['required', Rule::in(CheckoutController::STATES)], 'postcode' => ['required', 'regex:/^[1-9][0-9]{5}$/'],
            'phone' => ['nullable', 'regex:/^[0-9+\-\s]{10,15}$/'],
        ]);
        $d['country'] = 'IN';
        $address ? $address->update($d) : $request->user()->addresses()->create($d);
        return redirect()->route('account.addresses')->with('success', 'Address saved.');
    }

    public function deleteAddress(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        $address->delete();
        return back()->with('success', 'Address deleted.');
    }

    public function details()
    {
        return view('account.details');
    }

    public function updateDetails(Request $request)
    {
        $user = $request->user();
        $d = $request->validate([
            'name' => ['required', 'max:100'], 'phone' => ['nullable', 'regex:/^[0-9+\-\s]{10,15}$/'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'required_with:new_password'],
            'new_password' => ['nullable', 'confirmed', Password::defaults()],
        ]);
        if (! empty($d['new_password'])) {
            if (! Hash::check($d['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Your current password is incorrect.'])->withInput();
            }
            $user->password = $d['new_password'];
        }
        $user->fill(['name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'] ?? null])->save();
        return back()->with('success', 'Account details changed successfully.');
    }
}
