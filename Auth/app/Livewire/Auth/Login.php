<?php

namespace Modules\Auth\Livewire\Auth;

use App\Livewire\CFComponent;
use App\Traits\WithCustomLivewireException;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Facades\SidebarManager;
use Modules\Auth\Facades\UnsplashManager;
use Modules\Auth\Models\User;

class Login extends CFComponent
{
    use WithRateLimiting, WithCustomLivewireException;

    public $unsplash = [];

    public $rateLimitTime;

    public $user;

    public $username;

    public $password;

    public $remember;

    public $captcha;

    public function attemptLogin()
    {
        $this->validate([
            'username' => 'required',
            'password' => 'required',
            'remember' => 'nullable|boolean',
        ]);

        $this->checkIfUserExists($this->username);

        if (!$this->user) {
            return;
        }

        if (settings('auth.login.enable.captcha', config('auth.login.captcha'))) {
            $validator = Validator::make(['captcha' => $this->captcha], ['captcha' => 'required|captcha']);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'captcha' => __('auth::login.invalid_captcha'),
                ]);
            }
        }

        if (!Hash::check($this->password, $this->user->password)) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        if ($this->user->disabled) {
            Auth::logout();
            throw ValidationException::withMessages([
                'username' => __('auth::login.user_disabled'),
            ]);
        }

        Auth::login($this->user, $this->remember);

        if (settings('auth.login.redirect')) {
            $this->redirect(settings('auth.login.redirect'));
        }

        redirect()->intended();
    }

    public function checkIfUserExists($username)
    {
        $this->user = null;
        if (blank($username)) {
            return;
        }

        if ($this->setRateLimit()) {
            return;
        }

        $this->validate([
            'username' => 'required|exists:users,username',
        ], [
            'username.exists' => __('auth::login.user_not_found'),
        ]);

        $this->user = User::where('username', $username)->first();
        $this->resetErrorBag('username');
    }

    public function changeLanguage($language)
    {
        if ($language == request()->cookie('language')) {
            return;
        }
        cookie()->queue(cookie()->forget('language'));
        cookie()->queue(cookie()->forever('language', $language));

        $this->redirect(route('auth.login'));
    }

    public function setRateLimit(): bool
    {
        try {
            $this->rateLimit(settings('auth.login.rate_limit', config('auth.login.rate_limit')));
        } catch (TooManyRequestsException $exception) {
            $this->rateLimitTime = $exception->secondsUntilAvailable;

            return true;
        }

        return false;
    }

    public function mount()
    {
        $this->unsplash = UnsplashManager::returnBackground();

        if ($this->unsplash['error'] != null) {
            $this->log($this->unsplash['error'], 'error');
        }
    }

    public function render()
    {
        return $this->renderView('auth::livewire.auth.login', __('auth::login.tab_title'), 'auth::components.layouts.auth');
    }
}
