<?php

use App\Models\Setting;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    // Create two stores
    $this->store1 = Store::factory()->create(['name' => 'Store 1']);
    $this->store2 = Store::factory()->create(['name' => 'Store 2']);

    // Create users for each store
    $this->user1 = User::factory()->create([
        'store_id' => $this->store1->id,
        'email' => 'user1@test.com',
        'password' => Hash::make('password'),
    ]);

    $this->user2 = User::factory()->create([
        'store_id' => $this->store2->id,
        'email' => 'user2@test.com',
        'password' => Hash::make('password'),
    ]);
});

test('settings are isolated per store', function () {
    // Create settings for store 1
    $setting1 = Setting::create([
        'store_id' => $this->store1->id,
        'key' => 'currency',
        'value' => 'USD',
        'type' => 'string',
    ]);

    // Create settings for store 2
    $setting2 = Setting::create([
        'store_id' => $this->store2->id,
        'key' => 'currency',
        'value' => 'EUR',
        'type' => 'string',
    ]);

    // Verify store 1 only sees its settings
    $store1Settings = Setting::forStore($this->store1->id)->get();
    expect($store1Settings)->toHaveCount(1);
    expect($store1Settings->first()->value)->toBe('USD');

    // Verify store 2 only sees its settings
    $store2Settings = Setting::forStore($this->store2->id)->get();
    expect($store2Settings)->toHaveCount(1);
    expect($store2Settings->first()->value)->toBe('EUR');
});

test('settings require store_id', function () {
    $setting = new Setting([
        'key' => 'currency',
        'value' => 'USD',
        'type' => 'string',
    ]);

    expect(fn () => $setting->save())->toThrow(\Illuminate\Database\QueryException::class);
});

test('scoping by store works correctly', function () {
    Setting::create(['store_id' => $this->store1->id, 'key' => 'currency', 'value' => 'USD', 'type' => 'string']);
    Setting::create(['store_id' => $this->store1->id, 'key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'string']);
    Setting::create(['store_id' => $this->store1->id, 'key' => 'timezone', 'value' => 'UTC', 'type' => 'string']);
    Setting::create(['store_id' => $this->store2->id, 'key' => 'currency', 'value' => 'EUR', 'type' => 'string']);
    Setting::create(['store_id' => $this->store2->id, 'key' => 'language', 'value' => 'fr', 'type' => 'string']);

    $store1Settings = Setting::forStore($this->store1->id)->get();
    expect($store1Settings)->toHaveCount(3);

    $store2Settings = Setting::forStore($this->store2->id)->get();
    expect($store2Settings)->toHaveCount(2);
});

test('settings are unique per store and key combination', function () {
    Setting::create([
        'store_id' => $this->store1->id,
        'key' => 'currency',
        'value' => 'USD',
        'type' => 'string',
    ]);

    // Try to create duplicate key for same store
    expect(function () {
        Setting::create([
            'store_id' => $this->store1->id,
            'key' => 'currency',
            'value' => 'EUR',
            'type' => 'string',
        ]);
    })->toThrow(\Illuminate\Database\QueryException::class);

    // But same key for different store should work
    $setting2 = Setting::create([
        'store_id' => $this->store2->id,
        'key' => 'currency',
        'value' => 'EUR',
        'type' => 'string',
    ]);

    expect($setting2)->toBeInstanceOf(Setting::class);
});

test('value type casting works for string', function () {
    $setting = Setting::create([
        'store_id' => $this->store1->id,
        'key' => 'currency',
        'value' => 'USD',
        'type' => 'string',
    ]);

    expect($setting->value)->toBe('USD');
    expect($setting->type)->toBe('string');
});

test('value type casting works for integer', function () {
    $setting = Setting::create([
        'store_id' => $this->store1->id,
        'key' => 'max_items',
        'value' => '100',
        'type' => 'integer',
    ]);

    expect($setting->value)->toBe(100);
    expect(is_int($setting->value))->toBeTrue();
});

test('value type casting works for boolean', function () {
    $setting = Setting::create([
        'store_id' => $this->store1->id,
        'key' => 'enable_notifications',
        'value' => '1',
        'type' => 'boolean',
    ]);

    expect($setting->value)->toBeTrue();
    expect(is_bool($setting->value))->toBeTrue();

    $setting2 = Setting::create([
        'store_id' => $this->store1->id,
        'key' => 'enable_feature',
        'value' => '0',
        'type' => 'boolean',
    ]);

    expect($setting2->value)->toBeFalse();
});

test('value type casting works for json', function () {
    $jsonData = ['theme' => 'dark', 'layout' => 'grid'];
    $setting = Setting::create([
        'store_id' => $this->store1->id,
        'key' => 'preferences',
        'value' => json_encode($jsonData),
        'type' => 'json',
    ]);

    expect($setting->value)->toBeArray();
    expect($setting->value)->toBe($jsonData);
});

test('store relationship works correctly', function () {
    $setting = Setting::create([
        'store_id' => $this->store1->id,
        'key' => 'currency',
        'value' => 'USD',
        'type' => 'string',
    ]);

    expect($setting->store)->toBeInstanceOf(Store::class);
    expect($setting->store->id)->toBe($this->store1->id);
    expect($setting->store->name)->toBe('Store 1');
});

test('store settings relationship works correctly', function () {
    Setting::create(['store_id' => $this->store1->id, 'key' => 'currency', 'value' => 'USD', 'type' => 'string']);
    Setting::create(['store_id' => $this->store1->id, 'key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'string']);
    Setting::create(['store_id' => $this->store1->id, 'key' => 'timezone', 'value' => 'UTC', 'type' => 'string']);
    Setting::create(['store_id' => $this->store2->id, 'key' => 'currency', 'value' => 'EUR', 'type' => 'string']);
    Setting::create(['store_id' => $this->store2->id, 'key' => 'language', 'value' => 'fr', 'type' => 'string']);

    $store1 = Store::with('settings')->find($this->store1->id);
    expect($store1->settings)->toHaveCount(3);

    $store2 = Store::with('settings')->find($this->store2->id);
    expect($store2->settings)->toHaveCount(2);
});

test('scopeForCurrentUserStore filters by authenticated user store', function () {
    Setting::create(['store_id' => $this->store1->id, 'key' => 'currency', 'value' => 'USD', 'type' => 'string']);
    Setting::create(['store_id' => $this->store1->id, 'key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'string']);
    Setting::create(['store_id' => $this->store2->id, 'key' => 'currency', 'value' => 'EUR', 'type' => 'string']);
    Setting::create(['store_id' => $this->store2->id, 'key' => 'language', 'value' => 'fr', 'type' => 'string']);
    Setting::create(['store_id' => $this->store2->id, 'key' => 'timezone', 'value' => 'UTC', 'type' => 'string']);

    // Act as user from store 1
    $this->actingAs($this->user1, 'sanctum');

    $settings = Setting::forCurrentUserStore()->get();
    expect($settings)->toHaveCount(2);
    expect($settings->every(fn ($setting) => $setting->store_id === $this->store1->id))->toBeTrue();
});

test('getStoreId method returns store_id', function () {
    $setting = Setting::create([
        'store_id' => $this->store1->id,
        'key' => 'currency',
        'value' => 'USD',
        'type' => 'string',
    ]);

    expect($setting->getStoreId())->toBe($this->store1->id);
});

test('setting can be updated', function () {
    $setting = Setting::create([
        'store_id' => $this->store1->id,
        'key' => 'currency',
        'value' => 'USD',
        'type' => 'string',
    ]);

    $setting->update(['value' => 'EUR']);

    expect($setting->fresh()->value)->toBe('EUR');
});

test('setting can be soft deleted', function () {
    $setting = Setting::create([
        'store_id' => $this->store1->id,
        'key' => 'currency',
        'value' => 'USD',
        'type' => 'string',
    ]);

    $setting->delete();

    expect(Setting::find($setting->id))->toBeNull();
    expect(Setting::withTrashed()->find($setting->id))->not->toBeNull();
    expect(Setting::withTrashed()->find($setting->id)->deleted_at)->not->toBeNull();
});
