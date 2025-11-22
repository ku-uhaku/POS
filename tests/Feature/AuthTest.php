<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->baseUrl = '/api/v1/auth';
});

test('user can register with valid data', function () {
    $userData = [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'password123',
    ];

    $response = $this->postJson("{$this->baseUrl}/register", $userData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'first_name',
                    'last_name',
                    'full_name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'token',
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'User registered successfully',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'first_name' => 'Test',
        'last_name' => 'User',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

test('user cannot register with invalid email', function () {
    $userData = [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'invalid-email',
        'password' => 'password123',
    ];

    $response = $this->postJson("{$this->baseUrl}/register", $userData);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'email',
            ],
        ])
        ->assertJson([
            'success' => false,
        ]);
});

test('user cannot register with duplicate email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $userData = [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'existing@example.com',
        'password' => 'password123',
    ];

    $response = $this->postJson("{$this->baseUrl}/register", $userData);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'email',
            ],
        ]);
});

test('user cannot register without required fields', function () {
    $response = $this->postJson("{$this->baseUrl}/register", []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors',
        ])
        ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password']);
});

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $loginData = [
        'email' => 'test@example.com',
        'password' => 'password123',
    ];

    $response = $this->postJson("{$this->baseUrl}/login", $loginData);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'first_name',
                    'last_name',
                    'full_name',
                    'email',
                ],
                'token',
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Login successful',
        ]);

    expect($response->json('data.token'))->not->toBeEmpty();
});

test('user cannot login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $loginData = [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ];

    $response = $this->postJson("{$this->baseUrl}/login", $loginData);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors',
        ]);
});

test('user cannot login with non-existent email', function () {
    $loginData = [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
    ];

    $response = $this->postJson("{$this->baseUrl}/login", $loginData);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors',
        ]);
});

test('user cannot login without required fields', function () {
    $response = $this->postJson("{$this->baseUrl}/login", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

test('authenticated user can get their profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("{$this->baseUrl}/profile");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'first_name',
                    'last_name',
                    'full_name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ],
        ]);
});

test('unauthenticated user cannot get profile', function () {
    $response = $this->getJson("{$this->baseUrl}/profile");

    $response->assertStatus(401);
});

test('authenticated user can update their profile', function () {
    $user = User::factory()->create([
        'first_name' => 'Old',
        'last_name' => 'Name',
        'email' => 'old@example.com',
    ]);

    $updateData = [
        'first_name' => 'New',
        'last_name' => 'Name',
        'email' => 'new@example.com',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("{$this->baseUrl}/profile", $updateData);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'first_name',
                    'last_name',
                    'full_name',
                    'email',
                ],
            ],
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => [
                    'first_name' => 'New',
                    'last_name' => 'Name',
                    'email' => 'new@example.com',
                ],
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'first_name' => 'New',
        'last_name' => 'Name',
        'email' => 'new@example.com',
    ]);
});

test('authenticated user can update only name', function () {
    $user = User::factory()->create([
        'first_name' => 'Old',
        'last_name' => 'Name',
        'email' => 'test@example.com',
    ]);

    $updateData = [
        'first_name' => 'New',
        'last_name' => 'Name',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("{$this->baseUrl}/profile", $updateData);

    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'first_name' => 'New',
        'last_name' => 'Name',
        'email' => 'test@example.com',
    ]);
});

test('authenticated user can update password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $updateData = [
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("{$this->baseUrl}/profile", $updateData);

    $response->assertStatus(200);

    $user->refresh();
    expect(Hash::check('new-password123', $user->password))->toBeTrue();
});

test('authenticated user cannot update email to existing email', function () {
    $user1 = User::factory()->create(['email' => 'user1@example.com']);
    $user2 = User::factory()->create(['email' => 'user2@example.com']);

    $updateData = [
        'email' => 'user1@example.com',
    ];

    $response = $this->actingAs($user2, 'sanctum')
        ->putJson("{$this->baseUrl}/profile", $updateData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('unauthenticated user cannot update profile', function () {
    $updateData = [
        'first_name' => 'New',
        'last_name' => 'Name',
    ];

    $response = $this->putJson("{$this->baseUrl}/profile", $updateData);

    $response->assertStatus(401);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("{$this->baseUrl}/logout");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJson([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);

    // Verify token is deleted
    expect($user->tokens()->count())->toBe(0);
});

test('unauthenticated user cannot logout', function () {
    $response = $this->postJson("{$this->baseUrl}/logout");

    $response->assertStatus(401);
});

test('user can logout and token is revoked', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token');

    expect($user->tokens()->count())->toBe(1);

    $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
        ->postJson("{$this->baseUrl}/logout");

    $response->assertStatus(200);

    expect($user->fresh()->tokens()->count())->toBe(0);
});
