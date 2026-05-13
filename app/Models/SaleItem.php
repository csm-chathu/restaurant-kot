<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'product_id', 'quantity', 'unit_price', 'discount', 'serving_ml', 'total',
    ];

    protected $casts = [
        'unit_price' => 'float',
        'discount'   => 'float',
        'serving_ml' => 'float',
        'total'      => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
