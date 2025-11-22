<?php

namespace App\Models;

use App\Traits\BelongsToStore;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    /** @use HasFactory<\Database\Factories\SettingFactory> */
    use BelongsToStore, HasAuditTrail, HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'key',
        'value',
        'type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
        ];
    }

    /**
     * Get the value attribute with proper type casting.
     */
    public function getValueAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Set the value attribute with proper type conversion.
     */
    public function setValueAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['value'] = null;

            return;
        }

        $this->attributes['value'] = match ($this->type) {
            'integer' => (string) $value,
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }
}
