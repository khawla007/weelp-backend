<?php

namespace Tests\Feature\Customer;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_get_own_profile(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/customer/profile');

        $response->assertOk()
            ->assertJsonStructure(['user']);
    }

    public function test_customer_can_update_profile(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')
            ->putJson('/api/customer/profile', [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_customer_can_change_password(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'password' => Hash::make('OldPassword@123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')
            ->putJson('/api/customer/password', [
                'current_password' => 'OldPassword@123',
                'password' => 'NewPassword@123',
                'password_confirmation' => 'NewPassword@123',
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_change_password_fails_with_wrong_current(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'password' => Hash::make('OldPassword@123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')
            ->putJson('/api/customer/password', [
                'current_password' => 'WrongPassword@1',
                'password' => 'NewPassword@123',
                'password_confirmation' => 'NewPassword@123',
            ]);

        $response->assertUnauthorized();
    }

    public function test_profile_returns_401_without_auth(): void
    {
        $response = $this->getJson('/api/customer/profile');

        $response->assertUnauthorized();
    }
}
