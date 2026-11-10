<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\RefundService;
use App\Support\StoreException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $q = Order::query()
            ->when($request->filled('q'), function ($w) use ($request) {
                $s = trim($request->query('q'));
                $like = '%'.addcslashes($s, '%_\\').'%';
                $w->where(fn ($x) => $x->where('order_number', ltrim($s, '#'))->orWhere('email', 'like', $like)->orWhere('billing', 'like', $like));
            })
            ->when($request->filled('status'), fn ($w) => $w->where('status', $request->query('status')))
            ->when($request->filled('payment_status'), fn ($w) => $w->where('payment_status', $request->query('payment_status')))
            ->when($request->filled('from'), fn ($w) => $w->where('placed_at', '>=', $request->date('from')->startOfDay()))
            ->when($request->filled('to'), fn ($w) => $w->where('placed_at', '<=', $request->date('to')->endOfDay()))
            ->when($request->query('data') === 'real', fn ($w) => $w->where('is_demo', false))
            ->when($request->query('data') === 'demo', fn ($w) => $w->where('is_demo', true));
        return view('admin.orders.index', ['orders' => $q->latest('placed_at')->paginate(20)->withQueryString()]);
    }

    public function show(Order $order)
    {
        $order->load(['items', 'histories.admin', 'payments', 'refunds.admin', 'user']);
        return view('admin.orders.show', ['order' => $order, 'next' => config("jbo.status_transitions.{$order->status}", [])]);
    }

    public function status(Request $request, Order $order, OrderService $orders)
    {
        $d = $request->validate(['status' => ['required', Rule::in(array_keys(config('jbo.order_statuses')))], 'note' => ['nullable', 'max:500']]);
        try {
            $orders->transition($order, $d['status'], $d['note'] ?? null, $request->user());
        } catch (StoreException $e) {
            return back()->with('error', $e->getMessage());
        }
        ActivityLog::record('order.status', $order, ['to' => $d['status']]);
        return back()->with('success', 'Order status updated.');
    }

    public function tracking(Request $request, Order $order, OrderService $orders)
    {
        $d = $request->validate([
            'courier_name' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'tracking_url' => ['nullable', 'url:http,https', 'max:500'],
        ]);
        $order->update(['courier_name' => $d['courier_name'] ?? null, 'tracking_number' => $d['tracking_number'] ?? null, 'tracking_url' => $d['tracking_url'] ?? null]);
        $orders->addNote($order, 'Tracking updated: '.trim(($d['courier_name'] ?? '').' '.($d['tracking_number'] ?? '')), $request->user());
        ActivityLog::record('order.tracking', $order);
        return back()->with('success', 'Tracking details saved.');
    }

    public function note(Request $request, Order $order, OrderService $orders)
    {
        $orders->addNote($order, $request->validate(['note' => ['required', 'max:1000']])['note'], $request->user());
        return back()->with('success', 'Note added.');
    }

    public function refund(Request $request, Order $order, RefundService $refunds)
    {
        abort_unless($request->user()->hasPermission('orders'), 403);
        $d = $request->validate(['qty' => ['nullable', 'array'], 'qty.*' => ['nullable', 'integer', 'min:0'], 'amount' => ['nullable', 'numeric', 'min:0.01'], 'reason' => ['nullable', 'max:250']]);
        try {
            $refunds->refund($order, $d['qty'] ?? [], isset($d['amount']) ? (float) $d['amount'] : null, $request->boolean('restock'), $d['reason'] ?? null, $request->user());
        } catch (StoreException $e) {
            return back()->with('error', $e->getMessage());
        }
        ActivityLog::record('order.refund', $order, ['amount' => $d['amount'] ?? 'items']);
        return back()->with('success', 'Refund recorded.');
    }
}
