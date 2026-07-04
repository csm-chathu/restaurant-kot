<?php

namespace App\Http\Controllers\Api;

use App\Events\CustomerOrderPlaced;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\OrderNotification;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\WebPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UberWebhookController extends Controller
{
    /**
     * POST /api/webhook/uber/{branchId}
     *
     * Uber Eats posts order webhooks here. No Sanctum auth — verified by
     * optional UBER_WEBHOOK_SECRET HMAC signature check.
     */
    public function receive(Request $request, int $branchId)
    {
        // ── Signature verification (optional but recommended) ──────────────
        $secret = config('services.uber.webhook_secret');
        if ($secret) {
            $sig  = $request->header('X-Uber-Signature') ?? $request->header('X-Postmates-Signature') ?? '';
            $expected = hash_hmac('sha256', $request->getContent(), $secret);
            if (!hash_equals($expected, $sig)) {
                Log::warning('[Uber] Invalid webhook signature', ['branch' => $branchId]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->json()->all();
        Log::info('[Uber] Webhook received', ['branch' => $branchId, 'type' => $payload['type'] ?? 'unknown']);

        // Only process new order events
        $type = $payload['type'] ?? $payload['event_type'] ?? '';
        if (!in_array($type, ['orders.notification', 'eats.order', 'order.created', ''])) {
            return response()->json(['ok' => true]); // ack other event types
        }

        $branch = Branch::find($branchId);
        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }

        try {
            $sale = DB::transaction(function () use ($payload, $branch) {
                $parsed = $this->parseUberPayload($payload);

                // Find or create a placeholder customer
                $customer = Customer::firstOrCreate(
                    ['phone' => 'UBER', 'branch_id' => $branch->id],
                    ['name' => 'Uber Eats', 'branch_id' => $branch->id]
                );

                $invoiceNumber = $this->generateInvoiceNumber($branch->id);

                $sale = Sale::create([
                    'branch_id'      => $branch->id,
                    'invoice_number' => $invoiceNumber,
                    'customer_id'    => $customer->id,
                    'user_id'        => null,
                    'subtotal'       => $parsed['subtotal'],
                    'discount'       => 0,
                    'tax'            => 0,
                    'tax_rate'       => 0,
                    'service_charge' => 0,
                    'service_charge_rate' => 0,
                    'total'          => $parsed['total'],
                    'payment_method' => 'uber',
                    'payment_status' => 'paid', // Uber collects payment
                    'amount_paid'    => $parsed['total'],
                    'status'         => 'draft',
                    'order_type'     => 'takeaway',
                    'source'         => 'uber',
                    'notes'          => 'Uber Order ID: ' . ($parsed['uber_order_id'] ?? 'N/A'),
                    'sold_at'        => now(),
                ]);

                foreach ($parsed['items'] as $item) {
                    SaleItem::create([
                        'sale_id'    => $sale->id,
                        'product_id' => null, // Uber items aren't linked to products automatically
                        'quantity'   => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount'   => 0,
                        'serving_ml' => 0,
                        'item_notes' => $item['notes'],
                        'total'      => $item['unit_price'] * $item['quantity'],
                    ]);
                }

                return $sale;
            });

            // Notify frontend via Pusher
            $itemsCount = count($this->parseUberPayload($payload)['items']);
            broadcast(new CustomerOrderPlaced(
                branchId:      $branchId,
                saleId:        $sale->id,
                invoiceNumber: $sale->invoice_number,
                tableNumber:   'Uber Eats',
                total:         $sale->total,
                itemsCount:    $itemsCount,
            ));

            return response()->json(['ok' => true, 'invoice' => $sale->invoice_number], 201);

        } catch (\Throwable $e) {
            Log::error('[Uber] Failed to create sale', ['error' => $e->getMessage(), 'payload' => $payload]);
            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    // ── Payload parser — handles multiple Uber API versions ─────────────────

    private function parseUberPayload(array $payload): array
    {
        // Try nested order object (v2 API)
        $order = $payload['order'] ?? $payload;

        $uberOrderId = $order['id'] ?? $payload['order_id'] ?? null;

        // Items
        $rawItems = $order['cart']['items']
            ?? $order['items']
            ?? $payload['cart']['items']
            ?? [];

        $items    = [];
        $subtotal = 0;

        foreach ($rawItems as $ri) {
            $name      = $ri['title'] ?? $ri['name'] ?? $ri['item_title'] ?? 'Item';
            $qty       = (int)  ($ri['quantity'] ?? 1);
            $unitPrice = $this->extractPrice($ri);
            $notes     = $name; // store name as note since product_id is null
            if (!empty($ri['customizations']) || !empty($ri['modifiers'])) {
                $mods  = $ri['customizations'] ?? $ri['modifiers'] ?? [];
                $notes .= ' (' . implode(', ', array_map(fn($m) => $m['title'] ?? $m['name'] ?? '', $mods)) . ')';
            }
            $lineTotal  = $unitPrice * $qty;
            $subtotal  += $lineTotal;
            $items[]    = compact('name', 'qty', 'quantity', 'unit_price', 'notes') + ['unit_price' => $unitPrice, 'quantity' => $qty];
        }

        // Total — prefer the payment total if present
        $total = $this->extractTotal($order, $payload) ?: $subtotal;

        return compact('uberOrderId', 'items', 'subtotal', 'total');
    }

    private function extractPrice(array $item): float
    {
        // v2: price.unit_price.total_price (in cents or smallest unit)
        $raw = $item['price']['unit_price']['total_price']
            ?? $item['price']['unit_price']['base_unit_price']
            ?? $item['unit_price']
            ?? $item['price']
            ?? 0;

        // Uber sends prices in cents for some regions; detect by magnitude
        $val = (float) $raw;
        return $val > 1000 ? round($val / 100, 2) : $val;
    }

    private function extractTotal(array $order, array $payload): float
    {
        $raw = $order['payment']['charges']['total']['amount']
            ?? $order['payment']['total']
            ?? $payload['payment']['total']
            ?? $order['total']
            ?? 0;

        $val = (float) $raw;
        return $val > 100000 ? round($val / 100, 2) : $val;
    }

    private function generateInvoiceNumber(int $branchId): string
    {
        $prefix = 'UBR-' . date('Ymd');
        $last   = Sale::withTrashed()
            ->where('branch_id', $branchId)
            ->where('invoice_number', 'like', $prefix . '-%')
            ->lockForUpdate()
            ->selectRaw('MAX(CAST(SUBSTRING_INDEX(invoice_number, "-", -1) AS UNSIGNED)) as max_seq')
            ->value('max_seq') ?? 0;
        return $prefix . '-' . str_pad((int)$last + 1, 4, '0', STR_PAD_LEFT);
    }
}
