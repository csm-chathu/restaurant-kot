<?php

namespace App\Http\Controllers\Api;

use App\Events\CustomerOrderPlaced;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicOrderController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'    => 'required|exists:branches,id',
            'table_number' => 'required|string|max:50',
            'phone'        => 'required|string|max:20',
            'items'        => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1|max:50',
            'items.*.item_notes' => 'nullable|string|max:500',
        ]);

        // Validate table belongs to branch
        $table = Table::where('branch_id', $data['branch_id'])
            ->where('table_number', $data['table_number'])
            ->first();

        if (!$table) {
            return response()->json(['message' => 'Invalid table for this branch.'], 422);
        }

        // Find or create customer by phone
        $customer = Customer::firstOrCreate(
            ['phone' => $data['phone'], 'branch_id' => $data['branch_id']],
            ['name' => 'Guest ' . $data['phone'], 'branch_id' => $data['branch_id']]
        );

        return DB::transaction(function () use ($data, $customer) {
            $subtotal = 0;
            $itemRows = [];

            foreach ($data['items'] as $item) {
                $product  = Product::findOrFail($item['product_id']);
                $price    = (float) $product->selling_price;
                $qty      = (int) $item['quantity'];
                $lineTotal = $price * $qty;
                $subtotal += $lineTotal;

                $itemRows[] = [
                    'product'   => $product,
                    'qty'       => $qty,
                    'price'     => $price,
                    'lineTotal' => $lineTotal,
                    'notes'     => $item['item_notes'] ?? null,
                ];
            }

            $invoiceNumber = $this->generateInvoiceNumber($data['branch_id']);

            $sale = Sale::create([
                'branch_id'      => $data['branch_id'],
                'invoice_number' => $invoiceNumber,
                'customer_id'    => $customer->id,
                'customer_phone' => $data['phone'],
                'user_id'        => null,
                'subtotal'       => $subtotal,
                'discount'       => 0,
                'tax'            => 0,
                'tax_rate'       => 0,
                'service_charge' => 0,
                'service_charge_rate' => 0,
                'total'          => $subtotal,
                'payment_method' => 'cash',
                'payment_status' => 'pending',
                'amount_paid'    => 0,
                'status'         => 'draft',
                'order_type'     => 'dine_in',
                'table_number'   => $data['table_number'],
                'source'         => 'customer',
                'sold_at'        => now(),
            ]);

            foreach ($itemRows as $i) {
                $sale->items()->create([
                    'product_id'  => $i['product']->id,
                    'quantity'    => $i['qty'],
                    'unit_price'  => $i['price'],
                    'discount'    => 0,
                    'serving_ml'  => 0,
                    'item_notes'  => $i['notes'],
                    'total'       => $i['lineTotal'],
                ]);
            }

            broadcast(new CustomerOrderPlaced(
                branchId:      $data['branch_id'],
                saleId:        $sale->id,
                invoiceNumber: $sale->invoice_number,
                tableNumber:   $sale->table_number,
                total:         $sale->total,
                itemsCount:    count($itemRows),
            ));

            return response()->json([
                'message'        => 'Order placed successfully!',
                'invoice_number' => $sale->invoice_number,
                'table_number'   => $sale->table_number,
                'total'          => $sale->total,
                'items_count'    => count($itemRows),
            ], 201);
        });
    }

    private function generateInvoiceNumber(int $branchId): string
    {
        $prefix = 'CUS-' . date('Ymd');
        $last   = Sale::withTrashed()
            ->where('branch_id', $branchId)
            ->where('invoice_number', 'like', $prefix . '-%')
            ->lockForUpdate()
            ->selectRaw('MAX(CAST(SUBSTRING_INDEX(invoice_number, "-", -1) AS UNSIGNED)) as max_seq')
            ->value('max_seq') ?? 0;
        return $prefix . '-' . str_pad((int)$last + 1, 4, '0', STR_PAD_LEFT);
    }
}
