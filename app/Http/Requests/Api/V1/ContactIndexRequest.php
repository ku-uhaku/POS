<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ContactIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view contacts') ?? false;
    }

    /**
     * Handle failed authorization with a consistent API response.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'You do not have permission to view contacts.',
        ], 403));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(['client', 'supplier'])],
            'client_type' => ['nullable', Rule::in(['individual', 'company', 'government', 'nonprofit'])],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Extract filter values.
     *
     * @return array{type:?string,client_type:?string,store_id:?int,search:?string}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'type' => $validated['type'] ?? null,
            'client_type' => $validated['client_type'] ?? null,
            'store_id' => isset($validated['store_id']) ? (int) $validated['store_id'] : null,
            'search' => $validated['search'] ?? null,
        ];
    }

    /**
     * Determine pagination size with sensible defaults.
     */
    public function perPage(): int
    {
        $perPage = (int) ($this->validated()['per_page'] ?? 15);

        return max(1, min(100, $perPage));
    }
}
