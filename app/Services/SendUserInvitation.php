<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class SendUserInvitation
{
    /**
     * @return array{sent: bool, message: ?string}
     */
    public function send(User $user): array
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $user->forceFill(['invitation_sent_at' => now()])->save();

            return ['sent' => true, 'message' => null];
        }

        return [
            'sent' => false,
            'message' => is_string($status) ? __($status) : __('Не удалось отправить приглашение.'),
        ];
    }

    public function createInvitedManager(array $attributes): User
    {
        $fullName = trim(($attributes['last_name'] ?? '').' '.($attributes['first_name'] ?? ''));

        return User::query()->create([
            'name' => $fullName !== '' ? $fullName : ($attributes['email'] ?? ''),
            'first_name' => $attributes['first_name'],
            'last_name' => $attributes['last_name'],
            'phone' => $attributes['phone'] ?? null,
            'email' => $attributes['email'],
            'password' => bcrypt(Str::password(64)),
            'role' => $attributes['role'],
        ]);
    }
}
