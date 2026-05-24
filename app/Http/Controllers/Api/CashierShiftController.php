<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\CashierShiftCashOut;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierShiftController extends Controller
{
    private function openShiftQuery(Request $request)
    {
        $q = CashierShift::where('user_id', $request->user()->id)->where('status', 'open');
        $branchId = $request->user()->branch_id;
        if ($branchId) {
            $q->where('branch_id', $branchId);
        }
        return $q;
    }

    public function current(Request $request)
    {
        $shift = $this->openShiftQuery($request)
            ->with('user:id,name')
            ->latest('opened_at')
            ->first();

        return response()->json($shift ?: null);
    }

    public function open(Request $request)
    {
        $data = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
        ]);

        if ($this->openShiftQuery($request)->exists()) {
            return response()->json(['message' => 'You already have an open shift.'], 400);
        }

        $shift = CashierShift::create([
            'branch_id'    => $request->user()->branch_id,
            'user_id'      => $request->user()->id,
            'status'       => 'open',
            'opening_cash' => $data['opening_cash'],
            'opened_at'    => now(),
        ]);

        return response()->json($shift->load('user:id,name'), 201);
    }

    public function cashOut(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        $shift = $this->openShiftQuery($request)->latest('opened_at')->first();

        if (!$shift) {
            return response()->json(['message' => 'No open shift found.'], 404);
        }

        $cashOut = CashierShiftCashOut::create([
            'shift_id' => $shift->id,
            'user_id'  => $request->user()->id,
            'amount'   => $data['amount'],
            'reason'   => $data['reason'],
        ]);

        return response()->json($cashOut->load('user:id,name'), 201);
    }

    public function cashOuts(Request $request)
    {
        $shift = $this->openShiftQuery($request)->latest('opened_at')->first();

        if (!$shift) {
            return response()->json([]);
        }

        return response()->json(
            $shift->cashOuts()->with('user:id,name')->latest()->get()
        );
    }

    public function close(Request $request)
    {
        $data = $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'notes'        => 'nullable|string|max:500',
        ]);

        $shift = $this->openShiftQuery($request)
            ->latest('opened_at')
            ->first();

        if (!$shift) {
            return response()->json(['message' => 'No open shift found.'], 404);
        }

        $closedAt = now();

        // Aggregate sales during shift window
        $saleIds = Sale::where('branch_id', $shift->branch_id)
            ->where('status', 'completed')
            ->whereBetween('sold_at', [$shift->opened_at, $closedAt])
            ->pluck('id');

        $totalSalesCount = $saleIds->count();

        $totalRevenue = Sale::whereIn('id', $saleIds)->sum('total');

        // Payment method breakdown from SalePayment
        $paymentBreakdown = SalePayment::whereIn('sale_id', $saleIds)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method')
            ->map(fn($r) => (float) $r->total);

        $cashSales  = (float) ($paymentBreakdown['cash'] ?? 0);
        $cardSales  = (float) ($paymentBreakdown['card'] ?? 0);
        $otherSales = $paymentBreakdown->except(['cash', 'card'])->sum();
        $otherBreakdown = $paymentBreakdown->except(['cash', 'card'])->map(fn($v) => (float) $v);

        // Cash outs during shift
        $cashOutRecords = $shift->cashOuts()->with('user:id,name')->get();
        $totalCashOuts  = (float) $cashOutRecords->sum('amount');

        // Expected cash accounts for cash outs
        $expectedCash = (float) $shift->opening_cash + $cashSales - $totalCashOuts;
        $variance     = (float) $data['closing_cash'] - $expectedCash;

        // Category breakdown
        $categoryBreakdown = SaleItem::whereIn('sale_id', $saleIds)
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'categories.name',
                DB::raw('SUM(sale_items.quantity) as qty'),
                DB::raw('SUM(sale_items.total) as total')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'name'  => $r->name,
                'qty'   => (int) $r->qty,
                'total' => (float) $r->total,
            ]);

        $totalItems = (int) SaleItem::whereIn('sale_id', $saleIds)->sum('quantity');

        $shift->update([
            'closing_cash' => $data['closing_cash'],
            'closed_at'    => $closedAt,
            'status'       => 'closed',
            'notes'        => $data['notes'] ?? null,
        ]);

        return response()->json([
            'shift'              => $shift->load('user:id,name'),
            'total_sales_count'  => $totalSalesCount,
            'total_items'        => $totalItems,
            'total_revenue'      => $totalRevenue,
            'cash_sales'         => $cashSales,
            'card_sales'         => $cardSales,
            'other_sales'        => $otherSales,
            'other_breakdown'    => $otherBreakdown,
            'cash_outs'          => $cashOutRecords,
            'total_cash_outs'    => $totalCashOuts,
            'expected_cash'      => $expectedCash,
            'variance'           => $variance,
            'category_breakdown' => $categoryBreakdown,
        ]);
    }
}
