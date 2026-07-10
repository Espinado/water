<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Apartment;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LinkUserToApartment
{
    /**
     * @param  array{first_name?: string, last_name?: string, phone?: string|null}  $profile
     */
    public function link(User $user, Apartment $apartment, array $profile = []): void
    {
        if ($apartment->isOccupiedByOther($user->id)) {
            throw new \InvalidArgumentException(__('Квартира уже занята другим пользователем.'));
        }

        if ($user->occupiesApartment() && (int) $user->apartment_id !== (int) $apartment->id) {
            throw new \InvalidArgumentException(__('Пользователь уже привязан к другой квартире.'));
        }

        $payload = ['apartment_id' => $apartment->id];

        if ($profile !== []) {
            if (isset($profile['first_name'])) {
                $payload['first_name'] = $profile['first_name'];
            }
            if (isset($profile['last_name'])) {
                $payload['last_name'] = $profile['last_name'];
            }
            if (array_key_exists('phone', $profile)) {
                $payload['phone'] = $profile['phone'];
            }

            $first = $payload['first_name'] ?? $user->first_name;
            $last = $payload['last_name'] ?? $user->last_name;
            $fullName = trim((string) $last.' '.(string) $first);
            if ($fullName !== '') {
                $payload['name'] = $fullName;
            }
        }

        $user->forceFill($payload)->save();
    }

    public function unlink(User $user): void
    {
        $user->forceFill(['apartment_id' => null])->save();
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function createResident(Apartment $apartment, array $attributes): User
    {
        if ($apartment->isOccupiedByOther()) {
            throw new \InvalidArgumentException(__('Квартира уже занята.'));
        }

        $fullName = trim(($attributes['last_name'] ?? '').' '.($attributes['first_name'] ?? ''));

        return User::query()->create([
            'name' => $fullName !== '' ? $fullName : ($attributes['email'] ?? ''),
            'first_name' => $attributes['first_name'],
            'last_name' => $attributes['last_name'],
            'phone' => $attributes['phone'] ?? null,
            'email' => $attributes['email'],
            'password' => Hash::make(Str::password(64)),
            'role' => UserRole::Resident,
            'apartment_id' => $apartment->id,
        ]);
    }
}
