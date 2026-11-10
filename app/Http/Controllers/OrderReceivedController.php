<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderReceivedController extends Controller
{
    public function show(Request $request, Order $order)
    {
        PaymentController::authorizeOrder($request, $order);
        return view('store.order-received', ['order' => $order->load('items')]);
    }
}
