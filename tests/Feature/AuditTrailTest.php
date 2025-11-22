<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('audit trail sets created_by when creating a record', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin, 'sanctum');

    $newUser = User::create([
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@audit.com',
        'password' => Hash::make('password'),
    ]);

    expect($newUser->created_by)->toBe($admin->id);
    expect($newUser->updated_by)->toBeNull();
    expect($newUser->deleted_by)->toBeNull();
});

test('audit trail sets updated_by when updating a record', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create(['created_by' => $admin->id]);

    $updater = User::factory()->create();
    $this->actingAs($updater, 'sanctum');

    $user->update(['first_name' => 'Updated', 'last_name' => 'Name']);

    expect($user->fresh()->updated_by)->toBe($updater->id);
    expect($user->fresh()->created_by)->toBe($admin->id); // Should remain unchanged
});

test('audit trail sets deleted_by when soft deleting a record', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create(['created_by' => $admin->id]);

    $deleter = User::factory()->create();
    $this->actingAs($deleter, 'sanctum');

    $user->delete();

    expect($user->fresh()->deleted_by)->toBe($deleter->id);
    expect($user->fresh()->deleted_at)->not->toBeNull();
});

test('audit trail clears deleted_by when restoring a record', function () {
    $admin = User::factory()->create();
    $deleter = User::factory()->create();
    $this->actingAs($deleter, 'sanctum');

    $user = User::factory()->create(['created_by' => $admin->id]);
    $user->delete();

    expect($user->fresh()->deleted_by)->toBe($deleter->id);

    $user->restore();

    expect($user->fresh()->deleted_by)->toBeNull();
    expect($user->fresh()->deleted_at)->toBeNull();
});

test('audit trail relationships work correctly', function () {
    $creator = User::factory()->create();
    $updater = User::factory()->create();
    $this->actingAs($creator, 'sanctum');

    $user = User::create([
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@relationships.com',
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($updater, 'sanctum');
    $user->update(['first_name' => 'Updated', 'last_name' => 'Name']);

    $user->load(['creator', 'updater']);

    expect($user->creator->id)->toBe($creator->id);
    expect($user->updater->id)->toBe($updater->id);
});
