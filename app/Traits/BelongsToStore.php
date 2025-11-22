<?php

namespace App\Traits;

use App\Models\Store;
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
                $storeId = static::getStoreIdFromUser();
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
     * Scope a query to only include records for the current user's store.
     */
    public function scopeForCurrentUserStore($query)
    {
        $storeId = static::getStoreIdFromUser();
        if ($storeId) {
            return $query->where('store_id', $storeId);
        }

        return $query->whereRaw('1 = 0'); // Return empty result if no store_id found
    }

    /**
     * Get the store_id from the authenticated user.
     */
    protected static function getStoreIdFromUser(): ?int
    {
        // Try sanctum guard first (for API)
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();

            return $user->store_id ?? null;
        }

        // Fall back to default guard (for web)
        if (Auth::check()) {
            $user = Auth::user();

            return $user->store_id ?? null;
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
