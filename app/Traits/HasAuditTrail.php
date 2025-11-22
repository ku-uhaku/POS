<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

trait HasAuditTrail
{
    /**
     * Boot the trait.
     */
    protected static function bootHasAuditTrail(): void
    {
        // Set created_by when creating
        static::creating(function (Model $model): void {
            $userId = static::getCurrentUserId();
            if ($userId && $model->isFillable('created_by')) {
                $model->created_by = $userId;
            }
        });

        // Set updated_by when updating
        static::updating(function (Model $model): void {
            $userId = static::getCurrentUserId();
            if ($userId && $model->isFillable('updated_by')) {
                $model->updated_by = $userId;
            }
        });

        // Handle soft delete tracking
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::deleting(function (Model $model): void {
                $userId = static::getCurrentUserId();
                if ($model->isForceDeleting()) {
                    // For force delete, set deleted_by before deletion
                    if ($userId && $model->isFillable('deleted_by')) {
                        $model->deleted_by = $userId;
                        $model->saveQuietly(); // Save without triggering events
                    }
                } else {
                    // For soft delete, set deleted_by
                    if ($userId && $model->isFillable('deleted_by')) {
                        $model->deleted_by = $userId;
                        $model->saveQuietly(); // Save without triggering events
                    }
                }
            });

            // Clear deleted_by when restoring
            static::restoring(function (Model $model): void {
                if ($model->isFillable('deleted_by')) {
                    $model->deleted_by = null;
                }
            });
        } else {
            // For hard delete (without soft deletes)
            static::deleting(function (Model $model): void {
                $userId = static::getCurrentUserId();
                if ($userId && $model->isFillable('deleted_by')) {
                    $model->deleted_by = $userId;
                    $model->saveQuietly(); // Save without triggering events
                }
            });
        }
    }

    /**
     * Get the current authenticated user ID from the appropriate guard.
     */
    protected static function getCurrentUserId(): ?int
    {
        // Try sanctum guard first (for API)
        if (Auth::guard('sanctum')->check()) {
            return Auth::guard('sanctum')->id();
        }

        // Fall back to default guard (for web)
        if (Auth::check()) {
            return Auth::id();
        }

        return null;
    }

    /**
     * Get the user who created the record.
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated the record.
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the record.
     */
    public function deleter()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }
}

