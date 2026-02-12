<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannedLoginModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_banned_user_login_redirects_back_to_login_with_flag(): void
    {
        $user = User::factory()->create([
            'email' => 'banned@example.com',
            'password' => bcrypt('password'),
            'banned' => true,
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('login'))
          ->assertSessionHas('lc_banned', true);

        $this->assertGuest();
    }

    public function test_banned_route_is_removed(): void
    {
        $this->get('/banned')->assertNotFound();
    }
}
