<?php

namespace App\Traits;

use App\Models\Store;
use App\Services\StoreContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToStore
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToStore(): void
    {
        // Ensure store_id is set when creating if not provided
        static::creating(function (Model $model): void {
            if (! $model->store_id && $model->isFillable('store_id')) {
                $storeId = static::getStoreIdFromContext();
                if ($storeId) {
                    $model->store_id = $storeId;
                }
            }
        });
    }

    /**
     * Get the store that the model belongs to.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Scope a query to only include records for a specific store.
     */
    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Scope a query to only include records for the active store.
     */
    public function scopeForActiveStore($query)
    {
        $storeId = static::getStoreIdFromContext();
        if ($storeId) {
            return $query->where('store_id', $storeId);
        }

        return $query->whereRaw('1 = 0'); // Return empty result if no store_id found
    }

    /**
     * Scope a query to only include records for the current user's store.
     *
     * @deprecated Use scopeForActiveStore() instead
     */
    public function scopeForCurrentUserStore($query)
    {
        return static::scopeForActiveStore($query);
    }

    /**
     * Get the store_id from the active store context or user's default store.
     */
    protected static function getStoreIdFromContext(): ?int
    {
        // First, try to get from StoreContext (set by middleware)
        $activeStoreId = StoreContext::getActiveStore();
        if ($activeStoreId) {
            return $activeStoreId;
        }

        // Fall back to user's default store
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();

            return $user->default_store_id ?? $user->store_id ?? null;
        }

        if (Auth::check()) {
            $user = Auth::user();

            return $user->default_store_id ?? $user->store_id ?? null;
        }

        return null;
    }

    /**
     * Get the store ID for the current model instance.
     */
    public function getStoreId(): ?int
    {
        return $this->store_id;
    }
}
