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

        // Build query using HasQueryBuilder trait methods
        $query = Contact::query()
            ->withRelations('store')
            ->bulkFilter([
                'type' => $filters['type'],
                'client_type' => $filters['client_type'],
            ])
            ->search(
                ['contact_name', 'company_name', 'email', 'phone', 'mobile'],
                $filters['search']
            );

        // Apply store filtering using the BelongsToStore trait's automatic scoping
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

        // Apply sorting and pagination
        $contacts = $query
            ->applySorting('updated_at', 'desc')
            ->paginateWithDefaults($perPage);

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
