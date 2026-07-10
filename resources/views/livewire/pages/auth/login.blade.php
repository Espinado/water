<?php

use App\Livewire\Forms\LoginForm;
use App\Services\AppHost;
use App\Services\PwaContext;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public string $pwaApp = 'resident';

    public function mount(PwaContext $pwa, AppHost $appHost): void
    {
        $this->pwaApp = $appHost->isManager() ? AppHost::MANAGER : AppHost::RESIDENT;
        $pwa->rememberApp($this->pwaApp);
        View::share('pwaAppKey', $this->pwaApp);
    }

    public function login(PwaContext $pwa, AppHost $appHost): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $user = auth()->user();
        $homeRoute = $pwa->homeRoute($this->pwaApp);

        if ($this->pwaApp === AppHost::MANAGER && ! $user->canUseManagerApp()) {
            auth()->logout();
            session()->flash('login_error', __('Эта учётная запись не имеет доступа к приложению управляющего.'));

            return;
        }

        if ($this->pwaApp === AppHost::RESIDENT && ! $user->canUseResidentApp()) {
            auth()->logout();
            session()->flash('login_error', __('Эта учётная запись не имеет доступа к приложению жильца. Управляющему нужна назначенная квартира — обратитесь к коллеге или войдите в приложение управляющего.'));

            return;
        }

        $this->redirectIntended(default: route($homeRoute, absolute: false), navigate: false);
    }
}; ?>

<div>
    @php($forgotPasswordRoute = $pwaApp === 'manager' ? 'manager.password.request' : 'password.request')
    @php($pwaContext = app(PwaContext::class))

    <div class="mb-6 text-center">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Вход') }}</p>
        <p class="mt-1 text-lg font-bold text-slate-900">{{ app(\App\Services\PwaContext::class)->appConfig($pwaApp)['name'] }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    @if (session('login_error'))
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-900">{{ session('login_error') }}</div>
    @endif

    <form wire:submit="login">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="form.password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" @class([
                    'rounded border-gray-300 shadow-sm',
                    'text-red-600 focus:ring-red-500' => $pwaApp === 'manager',
                    'text-emerald-600 focus:ring-emerald-500' => $pwaApp !== 'manager',
                ]) name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if ($pwaApp === 'manager' && Route::has($forgotPasswordRoute))
                <a @class([
                    'underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2',
                    'focus:ring-red-500' => $pwaApp === 'manager',
                    'focus:ring-emerald-500' => $pwaApp !== 'manager',
                ]) href="{{ route($forgotPasswordRoute) }}" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button @class(['ms-3' => $pwaApp === 'manager']) wire:loading.attr="disabled" wire:target="login">
                <span wire:loading.remove wire:target="login">{{ __('Log in') }}</span>
                <span wire:loading wire:target="login">Lūdzu uzgaidiet</span>
            </x-primary-button>
        </div>
    </form>

    @if ($pwaApp === 'manager')
        <p class="mt-6 text-center text-xs text-slate-500">
            {{ __('Вы жилец?') }}
            <a href="{{ $pwaContext->installUrl('resident') }}" class="font-semibold text-emerald-700">{{ __('Приложение для жильца') }}</a>
        </p>
    @endif
</div>
