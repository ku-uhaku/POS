<?php

use App\Models\Contact;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create();
});

it('can apply bulk filters with exact match', function () {
    Contact::factory()->create(['type' => 'client', 'client_type' => 'company', 'store_id' => $this->store->id]);
    Contact::factory()->create(['type' => 'supplier', 'client_type' => 'individual', 'store_id' => $this->store->id]);

    $results = Contact::query()
        ->bulkFilter(['type' => 'client', 'client_type' => 'company'])
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->type)->toBe('client');
    expect($results->first()->client_type)->toBe('company');
});

it('can apply bulk filters with array values for in operations', function () {
    Contact::factory()->create(['type' => 'client', 'store_id' => $this->store->id]);
    Contact::factory()->create(['type' => 'supplier', 'store_id' => $this->store->id]);
    Contact::factory()->create(['type' => 'client', 'store_id' => $this->store->id]);

    $results = Contact::query()
        ->bulkFilter(['type' => ['client', 'supplier']])
        ->get();

    expect($results)->toHaveCount(3);
});

it('can apply bulk filters with date ranges', function () {
    $oldContact = Contact::factory()->create([
        'created_at' => '2023-01-01',
        'store_id' => $this->store->id,
    ]);
    $newContact = Contact::factory()->create([
        'created_at' => '2024-06-01',
        'store_id' => $this->store->id,
    ]);

    $results = Contact::query()
        ->bulkFilter([
            'created_at' => ['from' => '2024-01-01', 'to' => '2024-12-31'],
        ])
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($newContact->id);
});

it('ignores null and empty values in bulk filter', function () {
    Contact::factory()->count(3)->create(['store_id' => $this->store->id]);

    $results = Contact::query()
        ->bulkFilter([
            'type' => null,
            'client_type' => '',
            'store_id' => $this->store->id,
        ])
        ->get();

    expect($results)->toHaveCount(3);
});

it('can select specific fields', function () {
    Contact::factory()->create([
        'contact_name' => 'John Doe',
        'email' => 'john@example.com',
        'store_id' => $this->store->id,
    ]);

    $result = Contact::query()
        ->selectFields(['id', 'contact_name', 'email'])
        ->first();

    expect($result->getAttributes())->toHaveKeys(['id', 'contact_name', 'email']);
    expect($result->getAttributes())->not->toHaveKey('phone');
});

it('can select fields from comma-separated string', function () {
    Contact::factory()->create(['store_id' => $this->store->id]);

    $result = Contact::query()
        ->selectFields('id, contact_name, email')
        ->first();

    expect($result->getAttributes())->toHaveKeys(['id', 'contact_name', 'email']);
});

it('can apply sorting with single field', function () {
    Contact::factory()->create(['contact_name' => 'Alice', 'store_id' => $this->store->id]);
    Contact::factory()->create(['contact_name' => 'Bob', 'store_id' => $this->store->id]);
    Contact::factory()->create(['contact_name' => 'Charlie', 'store_id' => $this->store->id]);

    $results = Contact::query()
        ->applySorting('contact_name', 'asc')
        ->get();

    expect($results->first()->contact_name)->toBe('Alice');
    expect($results->last()->contact_name)->toBe('Charlie');
});

it('can apply sorting with multiple fields', function () {
    Contact::factory()->create(['contact_name' => 'Alice', 'created_at' => '2024-01-01', 'store_id' => $this->store->id]);
    Contact::factory()->create(['contact_name' => 'Alice', 'created_at' => '2024-02-01', 'store_id' => $this->store->id]);
    Contact::factory()->create(['contact_name' => 'Bob', 'created_at' => '2024-01-01', 'store_id' => $this->store->id]);

    $results = Contact::query()
        ->applySorting([
            'contact_name' => 'asc',
            'created_at' => 'desc',
        ])
        ->get();

    expect($results->first()->contact_name)->toBe('Alice');
    expect($results->first()->created_at->format('Y-m-d'))->toBe('2024-02-01');
});

it('can search across multiple columns', function () {
    Contact::factory()->create([
        'contact_name' => 'John Doe',
        'email' => 'john@example.com',
        'store_id' => $this->store->id,
    ]);
    Contact::factory()->create([
        'contact_name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'store_id' => $this->store->id,
    ]);

    $results = Contact::query()
        ->search(['contact_name', 'email'], 'john')
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->contact_name)->toBe('John Doe');
});

it('ignores empty search terms', function () {
    Contact::factory()->count(2)->create(['store_id' => $this->store->id]);

    $results = Contact::query()
        ->search(['contact_name', 'email'], null)
        ->get();

    expect($results)->toHaveCount(2);
});

it('can filter by date range', function () {
    Contact::factory()->create(['created_at' => '2024-01-15', 'store_id' => $this->store->id]);
    Contact::factory()->create(['created_at' => '2024-06-15', 'store_id' => $this->store->id]);
    Contact::factory()->create(['created_at' => '2025-01-15', 'store_id' => $this->store->id]);

    $results = Contact::query()
        ->dateRange('created_at', '2024-01-01', '2024-12-31')
        ->get();

    expect($results)->toHaveCount(2);
});

it('can filter by date range with only start date', function () {
    Contact::factory()->create(['created_at' => '2023-12-01', 'store_id' => $this->store->id]);
    Contact::factory()->create(['created_at' => '2024-06-01', 'store_id' => $this->store->id]);

    $results = Contact::query()
        ->dateRange('created_at', '2024-01-01')
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->created_at->format('Y-m-d'))->toBe('2024-06-01');
});

it('can paginate with defaults', function () {
    Contact::factory()->count(25)->create(['store_id' => $this->store->id]);

    $paginated = Contact::query()->paginateWithDefaults(10);

    expect($paginated->perPage())->toBe(10);
    expect($paginated->total())->toBe(25);
    expect($paginated->count())->toBe(10);
});

it('clamps per page between 1 and 100', function () {
    Contact::factory()->count(5)->create(['store_id' => $this->store->id]);

    $paginated = Contact::query()->paginateWithDefaults(150);
    expect($paginated->perPage())->toBe(100);

    $paginated = Contact::query()->paginateWithDefaults(0);
    expect($paginated->perPage())->toBe(1);
});

it('uses default per page of 15 when not specified', function () {
    Contact::factory()->count(20)->create(['store_id' => $this->store->id]);

    $paginated = Contact::query()->paginateWithDefaults();

    expect($paginated->perPage())->toBe(15);
});

it('can eager load relations', function () {
    $contact = Contact::factory()->create(['store_id' => $this->store->id]);

    $result = Contact::query()
        ->withRelations('store')
        ->first();

    expect($result->relationLoaded('store'))->toBeTrue();
});

it('can eager load multiple relations', function () {
    $contact = Contact::factory()->create(['store_id' => $this->store->id]);

    $result = Contact::query()
        ->withRelations(['store'])
        ->first();

    expect($result->relationLoaded('store'))->toBeTrue();
});

it('can apply request filters with field mapping', function () {
    Contact::factory()->create(['type' => 'client', 'store_id' => $this->store->id]);
    Contact::factory()->create(['type' => 'supplier', 'store_id' => $this->store->id]);

    $results = Contact::query()
        ->applyRequestFilters(
            ['contact_type' => 'client'],
            ['contact_type' => 'type']
        )
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->type)->toBe('client');
});

it('can chain multiple query builder methods', function () {
    Contact::factory()->create([
        'type' => 'client',
        'contact_name' => 'John Doe',
        'store_id' => $this->store->id,
    ]);
    Contact::factory()->create([
        'type' => 'supplier',
        'contact_name' => 'Jane Smith',
        'store_id' => $this->store->id,
    ]);

    $results = Contact::query()
        ->bulkFilter(['type' => 'client'])
        ->search(['contact_name'], 'John')
        ->applySorting('contact_name', 'asc')
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->contact_name)->toBe('John Doe');
});
