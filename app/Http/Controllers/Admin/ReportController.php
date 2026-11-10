<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Services\PopularProductsService;
use App\Services\ReportService;
use App\Support\Slug;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports)
    {
        [$from, $to, $demo] = DashboardController::range($request);
        return view('admin.reports.index', [
            'from' => $from, 'to' => $to, 'demo' => $demo,
            'summary' => $reports->summary($from, $to, $demo), 'byStatus' => $reports->ordersByStatus($from, $to, $demo),
            'series' => $reports->salesSeries($from, $to, $demo), 'products' => $reports->productSales($from, $to, $demo, 25),
            'inventory' => $reports->inventory(),
        ]);
    }

    /** Inspect exactly how Popular This Week is ranked right now. */
    public function popular(Request $request, PopularProductsService $popular, ReportService $reports)
    {
        $demo = $request->has('apply') ? $request->boolean('demo') : (bool) config('jbo.popular.include_demo');
        $from = now()->subDays(config('jbo.popular.window_days'));
        return view('admin.reports.popular', [
            'rows' => $reports->productSales($from, now(), $demo, 25), 'live' => $popular->get($demo), 'demo' => $demo, 'from' => $from,
            'rules' => config('jbo.popular'),
        ]);
    }

    public function export(Request $request, ReportService $reports, string $type)
    {
        abort_unless(in_array($type, ['sales', 'products', 'orders', 'inventory'], true), 404);
        [$from, $to, $demo] = DashboardController::range($request);
        ActivityLog::record('report.export', null, ['type' => $type]);

        return response()->streamDownload(function () use ($type, $reports, $from, $to, $demo) {
            $out = fopen('php://output', 'w');
            switch ($type) {
                case 'sales':
                    fputcsv($out, ['date', 'orders', 'revenue_inr']);
                    foreach ($reports->salesSeries($from, $to, $demo) as $r) { fputcsv($out, [$r['date'], $r['orders'], number_format($r['revenue'], 2, '.', '')]); }
                    break;
                case 'products':
                    fputcsv($out, ['product', 'units', 'orders', 'revenue_inr', 'last_sold']);
                    foreach ($reports->productSales($from, $to, $demo) as $r) { fputcsv($out, [Slug::csv($r['name']), $r['units'], $r['orders'], number_format($r['revenue'], 2, '.', ''), $r['last_sold']]); }
                    break;
                case 'orders':
                    fputcsv($out, ['order_number', 'placed_at', 'status', 'payment_status', 'payment_method', 'total', 'refunded', 'email', 'demo']);
                    Order::whereBetween('placed_at', [$from, $to])->when(! $demo, fn ($q) => $q->where('is_demo', false))->orderBy('id')->chunk(500, function ($rows) use ($out) {
                        foreach ($rows as $o) { fputcsv($out, [$o->order_number, $o->placed_at, $o->status, $o->payment_status, $o->payment_method, $o->total, $o->refunded_total, Slug::csv($o->email), $o->is_demo ? 'yes' : 'no']); }
                    });
                    break;
                case 'inventory':
                    fputcsv($out, ['product', 'variant', 'sku', 'stock', 'stock_managed']);
                    foreach ($reports->inventory() as $r) { fputcsv($out, [Slug::csv($r['product']), Slug::csv($r['label']), Slug::csv($r['sku']), $r['stock'], $r['managed'] ? 'yes' : 'no']); }
                    break;
            }
            fclose($out);
        }, "report-{$type}-".now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }
}
