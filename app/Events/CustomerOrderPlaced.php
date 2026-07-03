<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerOrderPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $branchId,
        public readonly int    $saleId,
        public readonly string $invoiceNumber,
        public readonly string $tableNumber,
        public readonly float  $total,
        public readonly int    $itemsCount,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("branch.{$this->branchId}.orders");
    }

    public function broadcastAs(): string
    {
        return 'customer.order.placed';
    }

    public function broadcastWith(): array
    {
        return [
            'sale_id'        => $this->saleId,
            'invoice_number' => $this->invoiceNumber,
            'table_number'   => $this->tableNumber,
            'total'          => $this->total,
            'items_count'    => $this->itemsCount,
            'at'             => now()->toTimeString(),
        ];
    }
}
