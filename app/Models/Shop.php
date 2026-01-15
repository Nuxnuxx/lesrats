<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'etsy_shop_id',
        'etsy_access_token',
        'etsy_refresh_token',
        'etsy_token_expires_at',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'etsy_token_expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'etsy_access_token',
        'etsy_refresh_token',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shop_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function members()
    {
        return $this->hasMany(ShopMembership::class);
    }
}
