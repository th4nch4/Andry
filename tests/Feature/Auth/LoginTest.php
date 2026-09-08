<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Laravel 7 has no travel() test helper, so move Carbon's clock directly.
     * The array cache store reads its expiry through Carbon, so the limiter follows.
     */
    private function travelSeconds($seconds)
    {
        Carbon::setTestNow(Carbon::now()->addSeconds($seconds));
    }

    private function user()
    {
        return factory(User::class)->create([
            'email' => 'demo@example.com',
        ]);
    }

    /**
     * Post a wrong password $times times.
     */
    private function failLogin($times = 1)
    {
        for ($i = 0; $i < $times; $i++) {
            $this->post('/login', [
                'email' => 'demo@example.com',
                'password' => 'wrong-password',
            ]);
        }
    }

    public function test_login_screen_renders_with_its_fields()
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('Lupa Password');
        $response->assertSee(route('password.request'), false);
    }

    public function test_root_redirects_to_login()
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_guest_cannot_reach_the_dashboard()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_login_with_correct_credentials()
    {
        $user = $this->user();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_a_wrong_password()
    {
        $this->user();

        $response = $this->post('/login', [
            'email' => 'demo@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_five_failures_are_allowed_before_the_lockout_opens()
    {
        $this->user();

        // Attempts 1-5 are rejected on credentials, not on throttling.
        $this->failLogin(5);

        $this->assertGuest();

        // The 6th is refused by the throttle, even with the correct password.
        $response = $this->post('/login', [
            'email' => 'demo@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringContainsString(
            'Too many login attempts',
            session('errors')->first('email')
        );
    }

    public function test_lockout_lasts_a_full_59_seconds_measured_from_the_fifth_failure()
    {
        $this->user();

        // A slow attacker: the first failure is well before the last one. The cooldown
        // must still run the full 59s from failure #5, not from failure #1.
        $this->failLogin(1);
        $this->travelSeconds(30);
        $this->failLogin(4);

        $this->post('/login', [
            'email' => 'demo@example.com',
            'password' => 'password',
        ]);

        $this->assertSame(LoginRequest::LOCKOUT_SECONDS, session('lockout_seconds'));
        $this->assertSame(59, session('lockout_seconds'));
    }

    public function test_lockout_releases_once_the_cooldown_elapses()
    {
        $user = $this->user();

        $this->failLogin(5);

        // One second short of the cooldown the door is still shut.
        $this->travelSeconds(58);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // Past 59s the correct password works again.
        $this->travelSeconds(60);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_lockout_fires_the_framework_lockout_event()
    {
        Event::fake([Lockout::class]);

        $this->user();
        $this->failLogin(6);

        Event::assertDispatched(Lockout::class);
    }

    public function test_a_successful_login_clears_the_failure_tally()
    {
        $user = $this->user();

        // Four failures, then a success, then four more must not add up to a lockout.
        $this->failLogin(4);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->post('/logout');
        $this->failLogin(4);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_throttle_is_scoped_per_email_so_one_victim_cannot_lock_out_another()
    {
        $this->user();
        $other = factory(User::class)->create(['email' => 'other@example.com']);

        $this->failLogin(5);

        // A different account from the same IP is unaffected.
        $this->post('/login', ['email' => $other->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($other);
    }

    public function test_user_can_logout()
    {
        $user = $this->user();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_forgot_password_screen_sends_a_reset_link()
    {
        Notification::fake();

        $user = $this->user();

        $this->get('/forgot-password')->assertOk();
        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_a_valid_token()
    {
        Notification::fake();

        $user = $this->user();
        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $this->get('/reset-password/'.$notification->token)->assertOk();

            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

            return true;
        });

        // The new password is the one that works now.
        $this->post('/login', ['email' => $user->email, 'password' => 'new-password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_reset_link_request_is_throttled_by_the_broker()
    {
        Notification::fake();

        $user = $this->user();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();

        // config/auth.php sets passwords.users.throttle to 60 seconds.
        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasErrors('email');

        // Only the first request produced a link.
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }
}
