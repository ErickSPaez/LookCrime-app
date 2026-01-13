<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUserInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_and_password_setup_email_is_sent(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['admin' => 1]);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Invited User',
            'email' => 'invited@example.com',
        ]);

        $response->assertRedirect(route('users-list'));

        $created = User::where('email', 'invited@example.com')->firstOrFail();

        Notification::assertSentTo($created, ResetPassword::class);
    }

    public function test_admin_can_resend_password_setup_email(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['admin' => 1]);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->post('/user/password/'.$user->id);

        $response->assertRedirect(route('users-list'));

        Notification::assertSentTo($user, ResetPassword::class);
    }
}
