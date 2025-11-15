<?php

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;        // ← ADD THIS
use Illuminate\Support\Facades\Log;       // ← ADD THIS
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        // Attempt authentication
        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // At this point credentials were valid and user is authenticated in the session.
        $user = Auth::user();
        $user = User::find(Auth::id()); // fetch fresh instance to avoid guard differences

        if (! $user) {
            Auth::logout();
            Log::warning("Login: authenticated but user record not found. id=" . Auth::id());
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        // If deactivated => deny (generic failure)
        if ($user->status === 'Deactivated') {
            Auth::logout();
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        // If inactive => promote to active (robustly)
        if ($user->status === 'Inactive') {
            try {
                // use direct attribute set (not mass-assignment) to avoid fillable issues
                $user->status = 'Active';
                $user->last_status_updated = now();
                $user->save();
                Log::info("Login: user promoted from inactive to active", ['user_id' => $user->id]);
            } catch (\Throwable $e) {
                // Log but don't reveal to user. If this fails, you can still proceed with login or force denial:
                Log::error("Login: failed to update user status", ['user_id' => $user->id, 'error' => $e->getMessage()]);
                // Optional: decide whether to allow login when update fails. Here we allow:
            }
        }

        // Successful, non-deactivated login: clear rate limiter and regenerate session
        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        DB::table('sessions')->where('id', session()->getId())->update(['user_id' => Auth::id()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('Email address')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@example.com"
        />

        <!-- Password -->
        <div class="relative">
            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            {{-- @if (Route::has('password.request'))
                <flux:link class="absolute end-0 top-0 text-sm" :href="route('password.request')" wire:navigate>
                    {{ __('Forgot your password?') }}
                </flux:link>
            @endif --}}
        </div>

        <!-- Remember Me -->
        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full bg-green-800">{{ __('Log in') }}</flux:button>
        </div>
    </form>

    @if (Route::has('register'))
        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Don\'t have an account?') }}
            <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
    @endif
</div>
