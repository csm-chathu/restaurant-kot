<?php

namespace App\Listeners;

use App\Events\CustomerOrderPlaced;
use App\Models\OrderNotification;
use App\Support\WebPushService;

class SaveOrderNotification
{
    public function handle(CustomerOrderPlaced $event): void
    {
        $title   = "New order — Table {$event->tableNumber}";
        $message = "{$event->itemsCount} item" . ($event->itemsCount !== 1 ? 's' : '') . ' · LKR ' . number_format($event->total, 2);

        // Persist to DB
        OrderNotification::create([
            'branch_id'      => $event->branchId,
            'sale_id'        => $event->saleId,
            'title'          => $title,
            'message'        => $message,
            'invoice_number' => $event->invoiceNumber,
            'table_number'   => $event->tableNumber,
            'total'          => $event->total,
            'items_count'    => $event->itemsCount,
        ]);

        // Web Push (background / mobile)
        WebPushService::sendToBranch($event->branchId, $title, $message, [
            'sale_id'        => $event->saleId,
            'invoice_number' => $event->invoiceNumber,
            'table_number'   => $event->tableNumber,
        ]);
    }
}
