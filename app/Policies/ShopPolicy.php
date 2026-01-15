<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ShopPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Shop $shop): bool
    {
        return $user->hasAccessToShop($shop);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Shop $shop): bool
    {
        return $user->shops()
            ->where('shops.id', $shop->id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Shop $shop): bool
    {
        return $user->shops()
            ->where('shops.id', $shop->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Shop $shop): bool
    {
        return $this->delete($user, $shop);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Shop $shop): bool
    {
        return $this->delete($user, $shop);
    }

    /**
     * Determine whether the user can manage members of the shop.
     */
    public function manageMembers(User $user, Shop $shop): bool
    {
        return $user->shops()
            ->where('shops.id', $shop->id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
    }
}
