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
     * Scope a query to only include records for the active store (from X-Store-ID header or default_store_id).
     * Validates that the authenticated user has access to the active store.
     */
    public function scopeForActiveStoreWithAccess($query, ?\App\Models\User $user = null)
    {
        $user = $user ?? Auth::guard('sanctum')->user() ?? Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0'); // No user = no access
        }

        // Get active store from StoreContext (respects X-Store-ID header or default_store_id)
        $activeStoreId = StoreContext::getActiveStore();

        if (! $activeStoreId) {
            return $query->whereRaw('1 = 0'); // No active store = no results
        }

        // Validate user has access to the active store
        if (! $user->hasAccessToStore($activeStoreId)) {
            return $query->whereRaw('1 = 0'); // No access = no results
        }

        return $query->where('store_id', $activeStoreId);
    }

    /**
     * Scope a query to only include records for stores the authenticated user can access.
     * This automatically resolves all accessible stores (store_id, default_store_id, and many-to-many stores).
     * Note: Use scopeForActiveStoreWithAccess() if you want to filter by the active store context instead.
     */
    public function scopeForAccessibleStores($query, ?\App\Models\User $user = null)
    {
        $user = $user ?? Auth::guard('sanctum')->user() ?? Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0'); // No user = no access
        }

        $accessibleStoreIds = static::getAccessibleStoreIds($user);

        if (empty($accessibleStoreIds)) {
            return $query->whereRaw('1 = 0'); // No accessible stores = no results
        }

        return $query->whereIn('store_id', $accessibleStoreIds);
    }

    /**
     * Scope a query to only include records for a specific store, with access validation.
     * Returns empty result if user doesn't have access to the specified store.
     */
    public function scopeForStoreWithAccess($query, int $storeId, ?\App\Models\User $user = null)
    {
        $user = $user ?? Auth::guard('sanctum')->user() ?? Auth::user();

        if (! $user || ! $user->hasAccessToStore($storeId)) {
            return $query->whereRaw('1 = 0'); // No access = no results
        }

        return $query->where('store_id', $storeId);
    }

    /**
     * Get all store IDs that a user can access.
     *
     * @return array<int>
     */
    protected static function getAccessibleStoreIds(\App\Models\User $user): array
    {
        $storeIds = collect([$user->store_id, $user->default_store_id])
            ->filter()
            ->map(fn ($id) => (int) $id);

        $relationStoreIds = $user->stores()->pluck('stores.id')->map(fn ($id) => (int) $id);

        return $storeIds
            ->merge($relationStoreIds)
            ->unique()
            ->values()
            ->all();
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
