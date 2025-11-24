<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StoreResource;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserStoreController extends Controller
{
    /**
     * Get all stores assigned to a user.
     * Requires: view users permission
     */
    public function index(User $user): JsonResponse
    {
        if (! auth('sanctum')->user()->can('view users')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $stores = $user->stores()->get();

        return $this->successResponse([
            'stores' => StoreResource::collection($stores),
            'default_store_id' => $user->default_store_id,
        ], 'User stores retrieved successfully');
    }

    /**
     * Assign a store to a user.
     * Requires: edit users permission
     */
    public function store(Request $request, User $user): JsonResponse
    {
        if (! auth('sanctum')->user()->can('edit users')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $storeId = $request->input('store_id');
        $store = Store::findOrFail($storeId);

        // Check if already assigned
        if ($user->stores()->where('stores.id', $storeId)->exists()) {
            return $this->errorResponse('User is already assigned to this store.', 422);
        }

        $user->stores()->attach($storeId);

        return $this->successResponse([
            'store' => new StoreResource($store),
        ], 'Store assigned to user successfully');
    }

    /**
     * Remove a store assignment from a user.
     * Requires: edit users permission
     */
    public function destroy(User $user, Store $store): JsonResponse
    {
        if (! auth('sanctum')->user()->can('edit users')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        // Check if user is assigned to this store
        if (! $user->stores()->where('stores.id', $store->id)->exists()) {
            return $this->notFoundResponse('User is not assigned to this store.');
        }

        // If this is the default store, clear it
        if ($user->default_store_id === $store->id) {
            $user->update(['default_store_id' => null]);
        }

        $user->stores()->detach($store->id);

        return $this->successResponse(null, 'Store assignment removed successfully');
    }

    /**
     * Set user's default store.
     * Requires: edit users permission
     */
    public function setDefaultStore(Request $request, User $user): JsonResponse
    {
        if (! auth('sanctum')->user()->can('edit users')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $storeId = $request->input('store_id');

        try {
            $user->setDefaultStore($storeId);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        $store = Store::findOrFail($storeId);

        return $this->successResponse([
            'store' => new StoreResource($store),
        ], 'Default store set successfully');
    }
}
