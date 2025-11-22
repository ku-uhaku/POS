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
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                });
            }),
            'permissions' => $this->when($request->user()?->can('view permissions'), function () {
                return $this->getAllPermissions()->pluck('name');
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'created_by' => $this->when($this->created_by, $this->created_by),
            'updated_by' => $this->when($this->updated_by, $this->updated_by),
            'deleted_at' => $this->when($this->deleted_at, fn () => $this->deleted_at->toIso8601String()),
            'deleted_by' => $this->when($this->deleted_by, $this->deleted_by),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ]),
            'updater' => $this->whenLoaded('updater', fn () => [
                'id' => $this->updater->id,
                'name' => $this->updater->name,
                'email' => $this->updater->email,
            ]),
            'deleter' => $this->whenLoaded('deleter', fn () => [
                'id' => $this->deleter->id,
                'name' => $this->deleter->name,
                'email' => $this->deleter->email,
            ]),
        ];
    }
}

