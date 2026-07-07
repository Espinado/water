<?php

namespace App\Livewire\Manager;

use App\Enums\UserRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class HouseholdPanel extends Component
{
    public string $new_building_name = '';

    public string $new_building_address = '';

    public ?int $building_id = null;

    public string $new_apartment_number = '';

    public string $resident_first_name = '';

    public string $resident_last_name = '';

    public string $resident_phone = '';

    public string $resident_email = '';

    public ?int $resident_apartment_id = null;

    public function mount(): void
    {
        $this->building_id = Building::query()->orderBy('id')->value('id');
    }

    public function updatedBuildingId(): void
    {
        $this->resident_apartment_id = null;
    }

    public function createBuilding(): void
    {
        $this->validate([
            'new_building_name' => ['required', 'string', 'max:255'],
            'new_building_address' => ['nullable', 'string', 'max:255'],
        ]);

        $building = Building::query()->create([
            'name' => $this->new_building_name,
            'address' => $this->new_building_address !== '' ? $this->new_building_address : null,
        ]);

        $this->building_id = $building->id;
        $this->new_building_name = '';
        $this->new_building_address = '';
        session()->flash('mgr_ok', __('Дом добавлен.'));
    }

    public function createApartment(): void
    {
        $this->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'new_apartment_number' => ['required', 'string', 'max:16'],
        ]);

        Apartment::query()->create([
            'building_id' => $this->building_id,
            'number' => $this->new_apartment_number,
        ]);

        $this->new_apartment_number = '';
        session()->flash('mgr_ok', __('Квартира добавлена.'));
    }

    public function createResident(): void
    {
        $this->validate([
            'resident_first_name' => ['required', 'string', 'max:100'],
            'resident_last_name' => ['required', 'string', 'max:100'],
            'resident_phone' => ['nullable', 'string', 'max:32'],
            'resident_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'resident_apartment_id' => ['required', 'exists:apartments,id'],
        ]);

        $fullName = trim($this->resident_last_name.' '.$this->resident_first_name);

        $user = User::query()->create([
            'name' => $fullName,
            'first_name' => $this->resident_first_name,
            'last_name' => $this->resident_last_name,
            'phone' => $this->resident_phone !== '' ? $this->resident_phone : null,
            'email' => $this->resident_email,
            'password' => Hash::make(Str::password(64)),
            'role' => UserRole::Resident,
            'apartment_id' => $this->resident_apartment_id,
            'email_verified_at' => now(),
        ]);

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $user->forceFill(['invitation_sent_at' => now()])->save();
            session()->flash('mgr_ok', __('Жилец добавлен. На :email отправлена ссылка: жилец придумает пароль и подтвердит его на сайте.', ['email' => $user->email]));
        } else {
            session()->flash('mgr_ok', __('Жилец добавлен. Письмо не отправилось — отправьте ссылку для пароля из таблицы «Квартиры».'));
            if (is_string($status)) {
                session()->flash('mgr_warn', __($status));
            }
        }

        $this->resident_first_name = '';
        $this->resident_last_name = '';
        $this->resident_phone = '';
        $this->resident_email = '';
        $this->resident_apartment_id = null;
    }

    #[Computed]
    public function buildings(): Collection
    {
        return Building::query()->withCount('apartments')->orderBy('name')->get();
    }

    #[Computed]
    public function apartmentsForBuilding(): Collection
    {
        if (! $this->building_id) {
            return collect();
        }

        return Apartment::query()
            ->where('building_id', $this->building_id)
            ->orderBy('number')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.manager.household-panel');
    }
}
