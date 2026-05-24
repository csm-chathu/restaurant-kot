<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    protected $fillable = [
        'branch_id', 'user_id', 'status',
        'opening_cash', 'closing_cash',
        'opened_at', 'closed_at', 'notes',
    ];

    protected $casts = [
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'opened_at'    => 'datetime',
        'closed_at'    => 'datetime',
    ];

    public function user()     { return $this->belongsTo(User::class); }
    public function branch()   { return $this->belongsTo(Branch::class); }
    public function cashOuts() { return $this->hasMany(CashierShiftCashOut::class, 'shift_id'); }
}
