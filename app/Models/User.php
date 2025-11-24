<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasAuditTrail, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The default guard name for Spatie Permission.
     */
    protected $guard_name = 'sanctum';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'age',
        'cin',
        'gender',
        'avatar',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'employee_id',
        'hire_date',
        'salary',
        'status',
        'store_id',
        'default_store_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hire_date' => 'date',
            'salary' => 'decimal:2',
            'age' => 'integer',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the store that the user belongs to (legacy - for backward compatibility).
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the user's default store.
     */
    public function defaultStore()
    {
        return $this->belongsTo(Store::class, 'default_store_id');
    }

    /**
     * Get all stores the user has access to (many-to-many).
     */
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'user_store')
            ->withTimestamps();
    }

    /**
     * Get stores where the user is an employee (legacy - single store assignment).
     */
    public function employeeStore()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Check if user has access to a specific store.
     */
    public function hasAccessToStore(int $storeId): bool
    {
        // Check if user is assigned to this store via many-to-many
        if ($this->stores()->where('stores.id', $storeId)->exists()) {
            return true;
        }

        // Fallback: check if it's the user's employee store (legacy)
        if ($this->store_id === $storeId) {
            return true;
        }

        return false;
    }

    /**
     * Set the user's default store.
     */
    public function setDefaultStore(int $storeId): void
    {
        // Verify user has access to this store
        if (! $this->hasAccessToStore($storeId)) {
            throw new \InvalidArgumentException('User does not have access to this store.');
        }

        $this->update(['default_store_id' => $storeId]);
    }
}
