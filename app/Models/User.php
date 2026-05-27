<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch_id',
        'can_override_gold_rate',
        'can_delete_transactions',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at'        => 'datetime',
        'password'                 => 'hashed',
        'can_override_gold_rate'   => 'boolean',
        'can_delete_transactions'  => 'boolean',
        'is_active'                => 'boolean',
        'branch_id'                => 'integer',
    ];

    public function canOverrideGoldRate(): bool
    {
        return $this->isAdmin() || $this->can_override_gold_rate;
    }

    public function canDeleteTransactions(): bool
    {
        return $this->isAdmin() || $this->can_delete_transactions;
    }

    public function canSellItems(): bool
    {
        return in_array($this->role, ['admin', 'owner', 'manager', 'cashier'], true);
    }

    public function canAddStock(): bool
    {
        return in_array($this->role, ['admin', 'owner', 'manager', 'store_keeper'], true);
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, ['admin', 'owner'], true);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function isStoreKeeper(): bool
    {
        return $this->role === 'store_keeper';
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'owner'], true);
    }
}
