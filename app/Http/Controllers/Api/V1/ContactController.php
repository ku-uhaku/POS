<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ContactIndexRequest;
use App\Http\Resources\Api\V1\ContactResource;
use App\Models\Contact;
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

        // Apply store filtering using the trait's automatic scoping
        if ($filters['store_id']) {
            // If specific store_id requested, validate access and filter
            if (! $user->hasAccessToStore($filters['store_id'])) {
                return $this->forbiddenResponse('You do not have access to this store.');
            }

            $query->forStoreWithAccess($filters['store_id'], $user);
        } else {
            // Otherwise, filter by active store (from X-Store-ID header or default_store_id)
            $query->forActiveStoreWithAccess($user);
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
}
