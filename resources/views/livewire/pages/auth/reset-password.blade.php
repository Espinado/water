<?php

use App\Models\User;
use App\Services\PwaContext;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $pwaApp = 'resident';

    public function mount(string $token, PwaContext $pwa): void
    {
        $this->token = $token;
        $this->email = request()->string('email');

        $requestedApp = request()->string('app');
        if ($requestedApp->isNotEmpty() && $pwa->isValidApp($requestedApp->toString())) {
            $this->pwaApp = $requestedApp->toString();
        } else {
            $user = User::query()->where('email', $this->email)->first();
            $this->pwaApp = $user ? $pwa->appKeyForUser($user) : 'resident';
        }

        $pwa->rememberApp($this->pwaApp);
        View::share('pwaAppKey', $this->pwaApp);
    }

    public function resetPassword(PwaContext $pwa): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = null;

        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function (User $resetUser) use (&$user) {
                $resetUser->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user = $resetUser;

                event(new PasswordReset($resetUser));
            }
        );

        if ($status != Password::PASSWORD_RESET || ! $user instanceof User) {
            $this->addError('email', __($status));

            return;
        }

        $this->pwaApp = $pwa->appKeyForUser($user);
        $pwa->rememberApp($this->pwaApp);

        Auth::login($user);
        Session::regenerate();

        $this->redirectRoute('pwa.install', [
            'app' => $this->pwaApp,
            'welcome' => 1,
        ], navigate: false);
    }
}; ?>

<div>
    <div class="mb-6 text-center">
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Установка пароля') }}</p>
        <p class="mt-1 text-lg font-bold text-slate-900">{{ config("pwa.apps.{$pwaApp}.name") }}</p>
    </div>

    <p class="mb-4 text-sm text-gray-600">
        {{ __('Придумайте пароль для входа и введите его дважды — во втором поле подтверждение должно совпадать с первым.') }}
    </p>

    <form wire:submit="resetPassword">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Новый пароль')" />
            <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Подтверждение пароля')" />

            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                          type="password"
                          name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Сохранить пароль') }}
            </x-primary-button>
        </div>
    </form>
</div>
