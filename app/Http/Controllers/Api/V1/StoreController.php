<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreRequest;
use App\Http\Resources\Api\V1\StoreResource;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    /**
     * Display a listing of stores.
     * Requires: view stores permission
     */
    public function index(): JsonResponse
    {
        if (! auth('sanctum')->user()->can('view stores')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $stores = Store::withCount('users')->paginate(15);

        return $this->successResponse([
            'stores' => StoreResource::collection($stores),
            'pagination' => [
                'current_page' => $stores->currentPage(),
                'last_page' => $stores->lastPage(),
                'per_page' => $stores->perPage(),
                'total' => $stores->total(),
            ],
        ], 'Stores retrieved successfully');
    }

    /**
     * Store a newly created store.
     * Requires: create stores permission
     */
    public function store(StoreRequest $request): JsonResponse
    {
        if (! auth('sanctum')->user()->can('create stores')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $store = Store::create($request->validated());

        return $this->createdResponse([
            'store' => new StoreResource($store),
        ], 'Store created successfully');
    }

    /**
     * Display the specified store.
     * Requires: view stores permission
     */
    public function show(Store $store): JsonResponse
    {
        if (! auth('sanctum')->user()->can('view stores')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        return $this->successResponse([
            'store' => new StoreResource($store->load('users')),
        ], 'Store retrieved successfully');
    }

    /**
     * Update the specified store.
     * Requires: edit stores permission
     */
    public function update(StoreRequest $request, Store $store): JsonResponse
    {
        if (! auth('sanctum')->user()->can('edit stores')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $store->update($request->validated());

        return $this->successResponse([
            'store' => new StoreResource($store->fresh()),
        ], 'Store updated successfully');
    }

    /**
     * Remove the specified store.
     * Requires: delete stores permission
     */
    public function destroy(Store $store): JsonResponse
    {
        if (! auth('sanctum')->user()->can('delete stores')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $store->delete();

        return $this->successResponse(null, 'Store deleted successfully');
    }
}
