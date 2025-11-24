<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerQueryBuilderMacros();
    }

    /**
     * Register query builder macros for global use.
     */
    protected function registerQueryBuilderMacros(): void
    {
        // Bulk filter macro
        EloquentBuilder::macro('bulkFilter', function (array $filters) {
            foreach ($filters as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                if (is_array($value) && ! empty($value) && ! isset($value['from']) && ! isset($value['to'])) {
                    $this->whereIn($key, $value);

                    continue;
                }

                if (is_array($value) && (isset($value['from']) || isset($value['to']))) {
                    if (isset($value['from'])) {
                        $this->where($key, '>=', $value['from']);
                    }
                    if (isset($value['to'])) {
                        $this->where($key, '<=', $value['to']);
                    }

                    continue;
                }

                $this->where($key, $value);
            }

            return $this;
        });

        // Select fields macro
        EloquentBuilder::macro('selectFields', function (array|string $fields) {
            if (is_string($fields)) {
                $fields = explode(',', $fields);
            }

            $fields = array_map('trim', $fields);
            $fields = array_filter($fields);

            if (! empty($fields)) {
                $this->select($fields);
            }

            return $this;
        });

        // Apply sorting macro
        EloquentBuilder::macro('applySorting', function (string|array $sort = 'id', string $defaultDirection = 'desc') {
            if (is_string($sort)) {
                $this->orderBy($sort, $defaultDirection);

                return $this;
            }

            foreach ($sort as $field => $direction) {
                $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
                $this->orderBy($field, $direction);
            }

            return $this;
        });

        // Search macro
        EloquentBuilder::macro('search', function (array $columns, ?string $searchTerm, string $operator = 'like') {
            if (empty($searchTerm) || empty($columns)) {
                return $this;
            }

            $searchTerm = trim($searchTerm);

            if ($operator === 'like') {
                $searchTerm = "%{$searchTerm}%";
            }

            $this->where(function ($builder) use ($columns, $searchTerm, $operator) {
                foreach ($columns as $index => $column) {
                    if ($index === 0) {
                        $builder->where($column, $operator, $searchTerm);
                    } else {
                        $builder->orWhere($column, $operator, $searchTerm);
                    }
                }
            });

            return $this;
        });

        // Date range macro
        EloquentBuilder::macro('dateRange', function (string $column, string|\DateTimeInterface|null $startDate = null, string|\DateTimeInterface|null $endDate = null) {
            if ($startDate) {
                $this->where($column, '>=', $startDate);
            }

            if ($endDate) {
                $this->where($column, '<=', $endDate);
            }

            return $this;
        });

        // Paginate with defaults macro
        EloquentBuilder::macro('paginateWithDefaults', function (?int $perPage = null, array $columns = ['*'], string $pageName = 'page', ?int $page = null) {
            $perPage = $perPage ?? 15;
            $perPage = max(1, min(100, $perPage));

            return $this->paginate($perPage, $columns, $pageName, $page);
        });

        // With relations macro
        EloquentBuilder::macro('withRelations', function (array|string $relations) {
            if (is_string($relations)) {
                $relations = explode(',', $relations);
            }

            $relations = array_map('trim', $relations);
            $relations = array_filter($relations);

            if (! empty($relations)) {
                $this->with($relations);
            }

            return $this;
        });

        // Apply request filters macro
        EloquentBuilder::macro('applyRequestFilters', function (array $requestFilters, array $fieldMapping = []) {
            $mappedFilters = [];

            foreach ($requestFilters as $key => $value) {
                $dbField = $fieldMapping[$key] ?? $key;

                if ($value === null || $value === '') {
                    continue;
                }

                $mappedFilters[$dbField] = $value;
            }

            return $this->bulkFilter($mappedFilters);
        });
    }
}
