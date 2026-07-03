<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'address', 'city', 'country', 'logo_path', 'logo_public_id', 'is_active', 'shop_type', 'service_charge_rate',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'service_charge_rate'  => 'float',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        $path = $this->logo_path;

        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
