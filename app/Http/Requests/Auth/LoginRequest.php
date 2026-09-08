<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Failed attempts allowed before the account/IP pair is locked out.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Length of the lockout, in seconds, once MAX_ATTEMPTS is reached.
     */
    public const LOCKOUT_SECONDS = 59;

    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to log the request's credentials in.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate()
    {
        $this->ensureIsNotLockedOut();

        if (! Auth::attempt($this->only('email', 'password'))) {
            $this->recordFailedAttempt();

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // A clean login wipes both the running tally and any expiring lockout.
        $this->limiter()->clear($this->attemptKey());
        $this->limiter()->clear($this->lockoutKey());

        $this->session()->regenerate();
    }

    /**
     * Seconds left on the current lockout, or 0 when not locked out.
     *
     * @return int
     */
    public function secondsUntilRetry()
    {
        return $this->limiter()->availableIn($this->lockoutKey());
    }

    /**
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureIsNotLockedOut()
    {
        // The lockout key holds a single hit, so one attempt on it means "locked".
        if (! $this->limiter()->tooManyAttempts($this->lockoutKey(), 1)) {
            return;
        }

        event(new Lockout($this));

        $seconds = $this->secondsUntilRetry();

        // Let the login view render a live countdown for the remaining time.
        $this->session()->flash('lockout_seconds', $seconds);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Count the failure and, on the MAX_ATTEMPTS-th one, open a fresh lockout window.
     *
     * @return void
     */
    protected function recordFailedAttempt()
    {
        $attempts = $this->limiter()->hit($this->attemptKey(), self::LOCKOUT_SECONDS);

        if ($attempts < self::MAX_ATTEMPTS) {
            return;
        }

        // Counting restarts from zero so the next lockout again needs MAX_ATTEMPTS failures.
        $this->limiter()->clear($this->attemptKey());

        // Written here rather than reusing the tally key: RateLimiter only sets a key's
        // timer on its first hit, so a shared key would time the cooldown from failure #1
        // and expire early. A dedicated key starts the full LOCKOUT_SECONDS now.
        $this->limiter()->hit($this->lockoutKey(), self::LOCKOUT_SECONDS);
    }

    /**
     * Laravel 7 has no RateLimiter facade, so resolve the cache limiter directly.
     *
     * @return \Illuminate\Cache\RateLimiter
     */
    protected function limiter()
    {
        return app(RateLimiter::class);
    }

    /**
     * @return string
     */
    protected function attemptKey()
    {
        return 'login-attempts:'.$this->rateLimitIdentifier();
    }

    /**
     * @return string
     */
    protected function lockoutKey()
    {
        return 'login-lockout:'.$this->rateLimitIdentifier();
    }

    /**
     * Throttle per email+IP pair so one attacker cannot lock a victim out globally.
     *
     * @return string
     */
    protected function rateLimitIdentifier()
    {
        return Str::lower($this->input('email')).'|'.$this->ip();
    }
}
