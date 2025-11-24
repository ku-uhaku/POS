<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'age' => $this->age,
            'cin' => $this->cin,
            'gender' => $this->gender,
            'avatar' => $this->avatar,
            'phone' => $this->phone,
            'address' => $this->address,
            'default_store' => $this->default_store_id,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'employee_id' => $this->employee_id,
            'hire_date' => $this->hire_date?->toDateString(),
            'salary' => $this->salary,
            'status' => $this->status,
            'roles' => $this->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
            ]),
            'store' => $this->stores->map(fn ($store) => [
                'id' => $store->id,
                'name' => $store->name,
                'code' => $store->code,
            ]),
            'permissions' => $this->getAllPermissions()->pluck('name'),
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
