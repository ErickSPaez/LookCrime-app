<?php

namespace Tests\Feature;

use App\Mail\TestSmtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminMailTestEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_test_email_from_management()
    {
        Gate::define('admin', fn (User $user) => true);

        $admin = User::factory()->create();

        Mail::fake();

        $this->actingAs($admin)
            ->post(route('users.mail.test'), ['test_email' => 'test@example.com'])
            ->assertRedirect();

        Mail::assertSent(TestSmtpMail::class, function (TestSmtpMail $mail) {
            return $mail->hasTo('test@example.com');
        });
    }
}
