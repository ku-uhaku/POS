<?php

namespace App\Models;

use App\Traits\BelongsToStore;
use App\Traits\HasAuditTrail;
use App\Traits\HasQueryBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use BelongsToStore;
    use HasAuditTrail;
    /** @use HasFactory<\Database\Factories\ContactFactory> */
    use HasFactory;

    use HasQueryBuilder;

    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'type',
        'client_type',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'mobile',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'tax_id',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Scope to filter by contact type (client vs supplier).
     */
    public function scopeOfType($query, ?string $type)
    {
        if (! $type) {
            return $query;
        }

        return $query->where('type', $type);
    }

    /**
     * Scope to filter by client type (individual, company, etc.).
     */
    public function scopeOfClientType($query, ?string $clientType)
    {
        if (! $clientType) {
            return $query;
        }

        return $query->where('client_type', $clientType);
    }

    /**
     * Scope to apply a search query.
     */
    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($builder) use ($search) {
            $builder
                ->where('contact_name', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%");
        });
    }

    /**
     * Contact belongs to a store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Cast definitions.
     */
    protected function casts(): array
    {
        return [
            'type' => 'string',
            'client_type' => 'string',
        ];
    }
}
