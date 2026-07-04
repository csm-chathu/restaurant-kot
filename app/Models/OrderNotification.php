<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderNotification extends Model
{
    protected $fillable = [
        'branch_id', 'sale_id', 'title', 'message',
        'invoice_number', 'table_number', 'total', 'items_count', 'read_at',
    ];

    protected $casts = [
        'total'   => 'float',
        'read_at' => 'datetime',
    ];
}
