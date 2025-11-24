<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ContactIndexRequest;
use App\Http\Resources\Api\V1\ContactResource;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    /**
     * Display a filtered list of contacts (clients & suppliers).
     */
    public function index(ContactIndexRequest $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->filters();
        $perPage = $request->perPage();

        $query = Contact::query()
            ->with('store')
            ->ofType($filters['type'])
            ->ofClientType($filters['client_type'])
            ->search($filters['search']);

        if ($filters['store_id']) {
            if (! $user->hasAccessToStore($filters['store_id'])) {
                return $this->forbiddenResponse('You do not have access to this store.');
            }

            $query->where('store_id', $filters['store_id']);
        } else {
            $accessibleStoreIds = $this->resolveAccessibleStoreIds($user);

            if (empty($accessibleStoreIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('store_id', $accessibleStoreIds);
            }
        }

        $contacts = $query
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return $this->successResponse([
            'contacts' => ContactResource::collection($contacts),
            'pagination' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ], 'Contacts retrieved successfully.');
    }

    /**
     * Resolve the list of store IDs the user can access.
     *
     * @return array<int,int>
     */
    private function resolveAccessibleStoreIds(User $user): array
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
}
