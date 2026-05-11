<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_BETA_TESTER = 'beta_tester';

    public const BETA_PHOTO_LIMIT = 75;
    public const BETA_SHOP_LIMIT = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /**
     * Mass-assignable. NE PAS ajouter `role` ou `ai_photos_count` ici :
     *   - role est attribué uniquement par RegisteredUserController (beta_tester)
     *     ou via la migration / commande artisan (admin).
     *   - ai_photos_count est uniquement modifié via $user->increment() en backend.
     * Les laisser fillable ouvrirait une escalade de privilèges si une route faisait
     * un jour fill($request->all()) ou update($request->all()).
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'fal_api_key',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'fal_api_key',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'fal_api_key' => 'encrypted',
            'ai_photos_count' => 'integer',
        ];
    }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedShops(): BelongsToMany
    {
        return $this->shops()->wherePivot('role', 'owner');
    }

    public function hasAccessToShop(Shop $shop): bool
    {
        return $this->shops()->where('shops.id', $shop->id)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isBetaTester(): bool
    {
        return $this->role === self::ROLE_BETA_TESTER;
    }

    /**
     * Whether the user can generate $count more AI photos without exceeding their lifetime quota.
     * Admins are always allowed. Beta testers are capped at BETA_PHOTO_LIMIT lifetime.
     */
    public function canGeneratePhotos(int $count = 1): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return ($this->ai_photos_count + $count) <= self::BETA_PHOTO_LIMIT;
    }

    /**
     * Whether the user can create another shop.
     * Admins unlimited; beta testers capped at BETA_SHOP_LIMIT owned shops.
     */
    public function canCreateShop(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->ownedShops()->count() < self::BETA_SHOP_LIMIT;
    }

    /**
     * Whether the user is allowed to enable the pricing expert mode on a shop.
     */
    public function canEnableExpertMode(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Remaining lifetime photos for the user, or null if unlimited (admin).
     */
    public function remainingPhotos(): ?int
    {
        if ($this->isAdmin()) {
            return null;
        }

        return max(0, self::BETA_PHOTO_LIMIT - $this->ai_photos_count);
    }
}
