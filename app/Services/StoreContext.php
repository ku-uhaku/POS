<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Auth;

class StoreContext
{
    protected static ?int $activeStoreId = null;

    /**
     * Set the active store ID for the current request.
     */
    public static function setActiveStore(int $storeId): void
    {
        static::$activeStoreId = $storeId;
    }

    /**
     * Get the active store ID.
     */
    public static function getActiveStore(): ?int
    {

        if (static::$activeStoreId !== null) {
            return static::$activeStoreId;
        }

        // Try to get from authenticated user's default store
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            if ($user->default_store_id) {
                return $user->default_store_id;
            }
        }

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->default_store_id) {
                return $user->default_store_id;
            }
        }

        return null;
    }

    /**
     * Get the active store model.
     */
    public static function getActiveStoreModel(): ?Store
    {
        $storeId = static::getActiveStore();
        if ($storeId) {
            return Store::find($storeId);
        }

        return null;
    }

    /**
     * Clear the active store context.
     */
    public static function clearActiveStore(): void
    {
        static::$activeStoreId = null;
    }

    /**
     * Check if a store is currently active.
     */
    public static function hasActiveStore(): bool
    {
        return static::getActiveStore() !== null;
    }
}
