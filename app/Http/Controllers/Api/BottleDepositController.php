<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BottleDeposit;
use Illuminate\Http\Request;

class BottleDepositController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = BottleDeposit::with(['product:id,name,sku', 'customer:id,name'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->type, fn($q, $t) => $q->where('type', $t))
            ->latest('processed_at');

        return response()->json($query->paginate($request->per_page ?? 30));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_id' => 'nullable|exists:sales,id',
            'customer_id' => 'nullable|exists:customers,id',
            'product_id' => 'nullable|exists:products,id',
            'type' => 'required|in:collect,refund,credit',
            'quantity' => 'required|numeric|min:0.001',
            'amount_per_bottle' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $totalAmount = (float) $data['quantity'] * (float) $data['amount_per_bottle'];
        $status = $data['type'] === 'collect' ? 'collected' : ($data['type'] === 'refund' ? 'refunded' : 'credited');

        $deposit = BottleDeposit::create([
            'branch_id' => $request->user()->branch_id,
            'sale_id' => $data['sale_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'status' => $status,
            'quantity' => $data['quantity'],
            'amount_per_bottle' => $data['amount_per_bottle'],
            'total_amount' => $totalAmount,
            'notes' => $data['notes'] ?? null,
            'processed_at' => now(),
        ]);

        return response()->json($deposit->load('product', 'customer'), 201);
    }

    public function processReturn(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'amount_per_bottle' => 'required|numeric|min:0',
            'method' => 'required|in:refund,credit',
            'notes' => 'nullable|string',
        ]);

        $total = (float) $data['quantity'] * (float) $data['amount_per_bottle'];

        $deposit = BottleDeposit::create([
            'branch_id' => $request->user()->branch_id,
            'customer_id' => $data['customer_id'] ?? null,
            'product_id' => $data['product_id'],
            'user_id' => $request->user()->id,
            'type' => $data['method'],
            'status' => $data['method'] === 'refund' ? 'refunded' : 'credited',
            'quantity' => $data['quantity'],
            'amount_per_bottle' => $data['amount_per_bottle'],
            'total_amount' => $total,
            'notes' => $data['notes'] ?? null,
            'processed_at' => now(),
        ]);

        return response()->json($deposit->load('product', 'customer'), 201);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $query = BottleDeposit::query();
        if (!$user->isAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        $collected = (clone $query)->where('type', 'collect')->sum('total_amount');
        $refunded = (clone $query)->where('type', 'refund')->sum('total_amount');
        $credited = (clone $query)->where('type', 'credit')->sum('total_amount');

        return response()->json([
            'collected' => (float) $collected,
            'refunded' => (float) $refunded,
            'credited' => (float) $credited,
            'outstanding' => (float) ($collected - $refunded - $credited),
        ]);
    }
}
