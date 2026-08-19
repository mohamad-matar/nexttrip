<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api';

    // ─── Register ────────────────────────────────────

    public function test_tourist_can_register(): void
    {
        $payload = [
            'name' => 'Ahmad',
            'email' => 'ahmad@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'tourist',
        ];

        $response = $this->postJson("{$this->baseUrl}/register", $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['token', 'user'],
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'email' => 'ahmad@test.com',
            'role' => UserRole::Tourist,
        ]);
    }

    public function test_guide_can_register_with_avatar(): void
    {
        $payload = [
            'name' => 'Sara',
            'email' => 'sara@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'guide',
            'gender' => 'F',
            'phone' => '0912345678',
            'DOB' => '1995-05-15',
            'daily_price' => 100,
            'bio' => 'I am a professional tour guide',
            'avatar' => \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg'),
        ];

        $response = $this->postJson("{$this->baseUrl}/register", $payload);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'email' => 'sara@test.com',
            'role' => UserRole::Guide,
        ]);

        $this->assertDatabaseHas('guides', [
            'user_id' => User::where('email', 'sara@test.com')->first()->id,
            'gender' => 'F',
            'daily_price' => 100,
        ]);
    }

    public function test_register_with_invalid_email_fails(): void
    {
        $payload = [
            'name' => 'Ahmad',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'tourist',
        ];

        $response = $this->postJson("{$this->baseUrl}/register", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_with_duplicate_email_fails(): void
    {
        User::factory()->create(['email' => 'exists@test.com']);

        $payload = [
            'name' => 'Ahmad',
            'email' => 'exists@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'tourist',
        ];

        $response = $this->postJson("{$this->baseUrl}/register", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_with_short_password_fails(): void
    {
        $payload = [
            'name' => 'Ahmad',
            'email' => 'ahmad@test.com',
            'password' => '12345',
            'password_confirmation' => '12345',
            'role' => 'tourist',
        ];

        $response = $this->postJson("{$this->baseUrl}/register", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_register_with_mismatched_password_confirmation_fails(): void
    {
        $payload = [
            'name' => 'Ahmad',
            'email' => 'ahmad@test.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
            'role' => 'tourist',
        ];

        $response = $this->postJson("{$this->baseUrl}/register", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_register_guide_missing_required_fields_fails(): void
    {
        $payload = [
            'name' => 'Sara',
            'email' => 'sara@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'guide',
            // missing: gender, phone, DOB, daily_price, bio
        ];

        $response = $this->postJson("{$this->baseUrl}/register", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['DOB', 'phone', 'daily_price', 'bio']);
    }

    public function test_register_returns_token(): void
    {
        $payload = [
            'name' => 'Ahmad',
            'email' => 'ahmad@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'tourist',
        ];

        $response = $this->postJson("{$this->baseUrl}/register", $payload);

        $response->assertStatus(201);
        $this->assertNotEmpty($response->json('data.token'));
    }

    // ─── Login ───────────────────────────────────────

    public function test_user_can_login(): void
    {
        $user = User::factory()->tourist()->create([
            'email' => 'ahmad@test.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson("{$this->baseUrl}/login", [
            'email' => 'ahmad@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['token', 'user'],
            ])
            ->assertJson(['success' => true]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_with_wrong_password_fails(): void
    {
        User::factory()->tourist()->create([
            'email' => 'ahmad@test.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson("{$this->baseUrl}/login", [
            'email' => 'ahmad@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_with_nonexistent_email_fails(): void
    {
        $response = $this->postJson("{$this->baseUrl}/login", [
            'email' => 'nobody@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_blocked_user_cannot_login(): void
    {
        User::factory()->tourist()->blocked()->create([
            'email' => 'blocked@test.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson("{$this->baseUrl}/login", [
            'email' => 'blocked@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_closed_user_cannot_login(): void
    {
        User::factory()->tourist()->closed()->create([
            'email' => 'closed@test.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson("{$this->baseUrl}/login", [
            'email' => 'closed@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    // ─── Logout ──────────────────────────────────────

    public function test_user_can_logout(): void
    {
        $user = User::factory()->tourist()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("{$this->baseUrl}/logout");

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson("{$this->baseUrl}/logout");

        $response->assertStatus(401);
    }

    // ─── Me ──────────────────────────────────────────

    public function test_user_can_get_me(): void
    {
        $user = User::factory()->tourist()->create([
            'name' => 'Ahmad',
            'email' => 'ahmad@test.com',
        ]);

        $response = $this->actingAs($user)
            ->getJson("{$this->baseUrl}/me");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Ahmad',
                    'email' => 'ahmad@test.com',
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_get_me(): void
    {
        $response = $this->getJson("{$this->baseUrl}/me");

        $response->assertStatus(401);
    }
}
