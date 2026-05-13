<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today     = today();
        $thisMonth = now()->startOfMonth();
        $user      = request()->user();

        $productsQuery  = Product::query();
        $customersQuery = Customer::query();
        $salesQuery     = Sale::query();
        $purchasesQuery = Purchase::query();

        if (!$user->isAdmin()) {
            $productsQuery->where('branch_id', $user->branch_id);
            $customersQuery->where('branch_id', $user->branch_id);
            $salesQuery->where('branch_id', $user->branch_id);
            $purchasesQuery->where('branch_id', $user->branch_id);
        }

        $completedSales = (clone $salesQuery)->where('status', 'completed');

        return response()->json([

            // ── KPI totals ──────────────────────────────────────────
            'totals' => [
                'products'        => (clone $productsQuery)->count(),
                'customers'       => (clone $customersQuery)->count(),
                'sales_today'     => (clone $completedSales)->whereDate('sold_at', $today)->count(),
                'revenue_today'   => (clone $completedSales)->whereDate('sold_at', $today)->where('payment_status', 'paid')->sum('total'),
                'revenue_month'   => (clone $completedSales)->where('sold_at', '>=', $thisMonth)->where('payment_status', 'paid')->sum('total'),
                'purchases_month' => (clone $purchasesQuery)->where('purchased_at', '>=', $thisMonth)->sum('total'),
                'low_stock_count' => (clone $productsQuery)->whereColumn('stock_quantity', '<=', 'min_stock_level')->count(),
                'pending_amount'  => (clone $completedSales)->where('payment_status', 'pending')->sum('total'),
                'pending_count'   => (clone $completedSales)->where('payment_status', 'pending')->count(),
            ],

            // ── 30-day revenue + bill count trend ───────────────────
            'sales_chart' => (clone $completedSales)
                ->select(
                    DB::raw('DATE(sold_at) as date'),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as count')
                )
                ->where('sold_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            // ── 6-month revenue bar ─────────────────────────────────
            'monthly_revenue' => (clone $completedSales)
                ->select(
                    DB::raw("DATE_FORMAT(sold_at, '%Y-%m') as month"),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as bill_count')
                )
                ->where('sold_at', '>=', now()->subMonths(5)->startOfMonth())
                ->groupBy('month')
                ->orderBy('month')
                ->get(),

            // ── Payment method breakdown (this month) ───────────────
            'payment_methods' => (clone $completedSales)
                ->select(
                    'payment_method',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total) as revenue')
                )
                ->where('sold_at', '>=', $thisMonth)
                ->groupBy('payment_method')
                ->orderByDesc('revenue')
                ->get(),

            // ── Category revenue breakdown (this month) ─────────────
            'category_sales' => DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->where('sales.status', 'completed')
                ->where('sales.sold_at', '>=', $thisMonth)
                ->whereNull('sales.deleted_at')
                ->when(!$user->isAdmin(), fn($q) => $q->where('sales.branch_id', $user->branch_id))
                ->select(
                    DB::raw("COALESCE(categories.name, 'Uncategorized') as category"),
                    DB::raw('SUM(sale_items.total) as revenue'),
                    DB::raw('COUNT(DISTINCT sales.id) as bill_count')
                )
                ->groupBy('categories.name')
                ->orderByDesc('revenue')
                ->get(),

            // ── Hourly sales pattern (last 7 days) ──────────────────
            'hourly_pattern' => (clone $completedSales)
                ->select(
                    DB::raw('HOUR(sold_at) as hour'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total) as revenue')
                )
                ->where('sold_at', '>=', now()->subDays(7))
                ->groupBy('hour')
                ->orderBy('hour')
                ->get(),

            // ── Top products this month ─────────────────────────────
            'top_products' => Product::select(
                    'products.id',
                    'products.name',
                    'products.image',
                    DB::raw('SUM(sale_items.quantity) as total_sold'),
                    DB::raw('SUM(sale_items.total) as total_revenue')
                )
                ->join('sale_items', 'products.id', '=', 'sale_items.product_id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.status', 'completed')
                ->where('sales.sold_at', '>=', $thisMonth)
                ->when(!$user->isAdmin(), fn($q) => $q->where('products.branch_id', $user->branch_id))
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total_revenue')
                ->take(6)
                ->get(),

            // ── Low stock alerts ────────────────────────────────────
            'low_stock' => (clone $productsQuery)
                ->with('category:id,name')
                ->whereColumn('stock_quantity', '<=', 'min_stock_level')
                ->take(10)
                ->get(['id', 'name', 'sku', 'stock_quantity', 'min_stock_level', 'category_id']),

            // ── Recent bills ────────────────────────────────────────
            'recent_sales' => (clone $salesQuery)
                ->with('customer:id,name')
                ->latest('sold_at')
                ->take(6)
                ->get(['id', 'invoice_number', 'customer_id', 'total', 'payment_status', 'sold_at', 'status']),
        ]);
    }
}
