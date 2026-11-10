<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\PopularProductsService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public static function range(Request $request): array
    {
        $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();
        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->subDays(29)->startOfDay();
        if ($from->gt($to)) { [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()]; }
        // Demo orders are hidden unless explicitly included (default: included only outside production, always labelled).
        $demo = $request->has('apply') ? $request->boolean('demo') : ! app()->isProduction();
        return [$from, $to, $demo];
    }

    public function index(Request $request, ReportService $reports, PopularProductsService $popular)
    {
        [$from, $to, $demo] = self::range($request);
        return view('admin.dashboard', [
            'from' => $from, 'to' => $to, 'demo' => $demo,
            'summary' => $reports->summary($from, $to, $demo),
            'byStatus' => $reports->ordersByStatus($from, $to, $demo),
            'series' => $reports->salesSeries($from, $to, $demo),
            'best' => $reports->productSales($from, $to, $demo, 5),
            'popular' => $popular->get($demo),
            'recent' => Order::when(! $demo, fn ($q) => $q->where('is_demo', false))->latest('placed_at')->limit(8)->get(),
            'lowStock' => Product::where('status', 'published')->where('manage_stock', true)->where('type', 'simple')->where('stock_qty', '<=', config('jbo.low_stock_threshold'))->orderBy('stock_qty')->limit(8)->get(),
            'counts' => ['products' => Product::where('status', 'published')->count(), 'drafts' => Product::where('status', 'draft')->count(), 'demoOrders' => Order::where('is_demo', true)->count(), 'realOrders' => Order::where('is_demo', false)->count()],
        ]);
    }
}
