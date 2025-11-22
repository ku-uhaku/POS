<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'users_count' => $this->whenCounted('users'),
            'users' => $this->whenLoaded('users', function () {
                return $this->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                    ];
                });
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'created_by' => $this->when($this->created_by, $this->created_by),
            'updated_by' => $this->when($this->updated_by, $this->updated_by),
            'deleted_at' => $this->when($this->deleted_at, fn () => $this->deleted_at->toIso8601String()),
            'deleted_by' => $this->when($this->deleted_by, $this->deleted_by),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'first_name' => $this->creator->first_name,
                'last_name' => $this->creator->last_name,
                'full_name' => $this->creator->full_name,
                'email' => $this->creator->email,
            ]),
            'updater' => $this->whenLoaded('updater', fn () => [
                'id' => $this->updater->id,
                'first_name' => $this->updater->first_name,
                'last_name' => $this->updater->last_name,
                'full_name' => $this->updater->full_name,
                'email' => $this->updater->email,
            ]),
            'deleter' => $this->whenLoaded('deleter', fn () => [
                'id' => $this->deleter->id,
                'first_name' => $this->deleter->first_name,
                'last_name' => $this->deleter->last_name,
                'full_name' => $this->deleter->full_name,
                'email' => $this->deleter->email,
            ]),
        ];
    }
}
