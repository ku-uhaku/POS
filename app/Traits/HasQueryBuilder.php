<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

trait HasQueryBuilder
{
    /**
     * Apply bulk filters to the query.
     * Supports exact match, like, in, between, and null checks.
     *
     * @param  array<string, mixed>  $filters
     * @return EloquentBuilder|QueryBuilder
     */
    public function scopeBulkFilter($query, array $filters)
    {
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Handle array values (for 'in' operations)
            if (is_array($value) && ! empty($value)) {
                $query->whereIn($key, $value);

                continue;
            }

            // Handle date range filters (expects ['from' => date, 'to' => date])
            if (is_array($value) && isset($value['from']) || isset($value['to'])) {
                if (isset($value['from'])) {
                    $query->where($key, '>=', $value['from']);
                }
                if (isset($value['to'])) {
                    $query->where($key, '<=', $value['to']);
                }

                continue;
            }

            // Default: exact match
            $query->where($key, $value);
        }

        return $query;
    }

    /**
     * Select specific fields from the query.
     *
     * @param  array<string>|string  $fields
     * @return EloquentBuilder|QueryBuilder
     */
    public function scopeSelectFields($query, array|string $fields)
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }

        $fields = array_map('trim', $fields);
        $fields = array_filter($fields);

        if (empty($fields)) {
            return $query;
        }

        return $query->select($fields);
    }

    /**
     * Apply sorting to the query.
     * Supports single field or multiple fields with directions.
     *
     * @param  string|array<string, string>  $sort
     * @return EloquentBuilder|QueryBuilder
     */
    public function scopeApplySorting($query, string|array $sort = 'id', string $defaultDirection = 'desc')
    {
        if (is_string($sort)) {
            $query->orderBy($sort, $defaultDirection);

            return $query;
        }

        // Handle array of sorts: ['name' => 'asc', 'created_at' => 'desc']
        foreach ($sort as $field => $direction) {
            $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($field, $direction);
        }

        return $query;
    }

    /**
     * Apply search across multiple columns.
     *
     * @param  array<string>  $columns
     * @return EloquentBuilder|QueryBuilder
     */
    public function scopeSearch($query, array $columns, ?string $searchTerm, string $operator = 'like')
    {
        if (empty($searchTerm) || empty($columns)) {
            return $query;
        }

        $searchTerm = trim($searchTerm);

        if ($operator === 'like') {
            $searchTerm = "%{$searchTerm}%";
        }

        return $query->where(function ($builder) use ($columns, $searchTerm, $operator) {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $builder->where($column, $operator, $searchTerm);
                } else {
                    $builder->orWhere($column, $operator, $searchTerm);
                }
            }
        });
    }

    /**
     * Apply date range filter.
     *
     * @return EloquentBuilder|QueryBuilder
     */
    public function scopeDateRange($query, string $column, string|\DateTimeInterface|null $startDate = null, string|\DateTimeInterface|null $endDate = null)
    {
        if ($startDate) {
            $query->where($column, '>=', $startDate);
        }

        if ($endDate) {
            $query->where($column, '<=', $endDate);
        }

        return $query;
    }

    /**
     * Paginate with sensible defaults.
     *
     * @param  array<string>  $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function scopePaginateWithDefaults($query, ?int $perPage = null, array $columns = ['*'], string $pageName = 'page', ?int $page = null)
    {
        $perPage = $perPage ?? 15;
        $perPage = max(1, min(100, $perPage)); // Clamp between 1 and 100

        return $query->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Eager load relationships with optional constraints.
     *
     * @param  array<string>|string  $relations
     * @return EloquentBuilder|QueryBuilder
     */
    public function scopeWithRelations($query, array|string $relations)
    {
        if (is_string($relations)) {
            $relations = explode(',', $relations);
        }

        $relations = array_map('trim', $relations);
        $relations = array_filter($relations);

        if (empty($relations)) {
            return $query;
        }

        return $query->with($relations);
    }

    /**
     * Apply filters from request with mapping support.
     * Allows mapping request keys to database columns.
     *
     * @param  array<string, mixed>  $requestFilters
     * @param  array<string, string>  $fieldMapping
     * @return EloquentBuilder|QueryBuilder
     */
    public function scopeApplyRequestFilters($query, array $requestFilters, array $fieldMapping = [])
    {
        $mappedFilters = [];

        foreach ($requestFilters as $key => $value) {
            // Use mapped field name if available, otherwise use original key
            $dbField = $fieldMapping[$key] ?? $key;

            // Skip null or empty values
            if ($value === null || $value === '') {
                continue;
            }

            $mappedFilters[$dbField] = $value;
        }

        return $this->scopeBulkFilter($query, $mappedFilters);
    }
}
