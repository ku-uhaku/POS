<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     * Requires: view users permission
     */
    public function index(): JsonResponse
    {
        if (! auth('sanctum')->user()->can('view users')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $activeStoreId = StoreContext::getActiveStore();

        // Build query using HasQueryBuilder trait methods
        $usersQuery = User::query()
            ->withRelations(['roles', 'store', 'defaultStore', 'stores']);

        // Filter by active store if set
        if ($activeStoreId) {
            $usersQuery->where(function ($query) use ($activeStoreId) {
                $query->where('store_id', $activeStoreId)
                    ->orWhere('default_store_id', $activeStoreId)
                    ->orWhereHas('stores', function ($storeQuery) use ($activeStoreId) {
                        $storeQuery->where('stores.id', $activeStoreId);
                    });
            });
        }

        // Apply pagination using trait method
        $users = $usersQuery->paginateWithDefaults(15);

        return $this->successResponse([
            'users' => UserResource::collection($users),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ], 'Users retrieved successfully');
    }

    /**
     * Display the specified user.
     * Requires: view users permission
     */
    public function show(User $user): JsonResponse
    {
        if (! auth('sanctum')->user()->can('view users')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $activeStoreId = StoreContext::getActiveStore();

        if ($activeStoreId && ! $user->hasAccessToStore($activeStoreId)) {
            abort(403, 'You do not have access to this user.');
        }

        return $this->successResponse([
            'user' => new UserResource($user->load(['roles', 'store', 'defaultStore', 'stores'])),
        ], 'User retrieved successfully');
    }

    /**
     * Remove the specified user from storage.
     * Requires: delete users permission
     */
    public function destroy(User $user): JsonResponse
    {
        if (! auth('sanctum')->user()->can('delete users')) {
            abort(403, 'You do not have the required permission to perform this action.');
        }

        $activeStoreId = StoreContext::getActiveStore();

        if ($activeStoreId && ! $user->hasAccessToStore($activeStoreId)) {
            abort(403, 'You do not have access to this user.');
        }

        $user->delete();

        return $this->successResponse(null, 'User deleted successfully');
    }
}
