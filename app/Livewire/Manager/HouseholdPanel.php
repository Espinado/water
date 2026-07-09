<?php

namespace App\Livewire\Manager;

use App\Enums\UserRole;
use App\Livewire\Concerns\HasManagerContext;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use App\Services\ManagerContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.manager')]
class HouseholdPanel extends Component
{
    use HasManagerContext;
    use WithPagination;

    public ?int $building_id = null;

    public bool $inBuilding = false;

    public string $search = '';

    public string $sortField = 'number';

    public bool $sortAsc = true;

    public string $new_building_name = '';

    public string $new_building_address = '';

    public bool $creatingApartment = false;

    public string $new_apartment_number = '';

    public string $new_apartment_area_m2 = '';

    public ?int $editingBuildingId = null;

    public string $edit_building_name = '';

    public string $edit_building_address = '';

    public ?int $editingApartmentId = null;

    public string $edit_apartment_number = '';

    public string $edit_apartment_area_m2 = '';

    public ?int $editingResidentId = null;

    public ?int $creatingResidentForApartmentId = null;

    public string $resident_first_name = '';

    public string $resident_last_name = '';

    public string $resident_phone = '';

    public string $resident_email = '';

    public string $edit_resident_first_name = '';

    public string $edit_resident_last_name = '';

    public string $edit_resident_phone = '';

    public string $edit_resident_email = '';

    public function mount(ManagerContext $context): void
    {
        $this->loadManagerContext($context);
    }

    protected function syncPeriodFromContext(ManagerContext $context): void
    {
        // Период на этой странице не используется.
    }

    protected function managerPeriodYear(): int
    {
        return (int) now()->year;
    }

    protected function managerPeriodMonth(): int
    {
        return (int) now()->month;
    }

    public function updatedBuildingId(ManagerContext $context): void
    {
        if ($this->building_id) {
            $context->setBuildingId((int) $this->building_id);
        }
        $this->cancelAllForms();
    }

    public function openBuilding(int $buildingId, ManagerContext $context): void
    {
        Building::query()->findOrFail($buildingId);
        $this->building_id = $buildingId;
        $context->setBuildingId($buildingId);
        $this->inBuilding = true;
        $this->resetPage();
        $this->cancelAllForms();
    }

    public function backToBuildings(): void
    {
        $this->inBuilding = false;
        $this->search = '';
        $this->sortField = 'number';
        $this->sortAsc = true;
        $this->resetPage();
        $this->cancelAllForms();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortAsc = ! $this->sortAsc;
        } else {
            $this->sortField = $field;
            $this->sortAsc = true;
        }
        $this->resetPage();
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
        app(ManagerContext::class)->setBuildingId($building->id);
        $this->new_building_name = '';
        $this->new_building_address = '';
        session()->flash('mgr_ok', __('Дом добавлен.'));
    }

    public function startEditBuilding(int $buildingId): void
    {
        $building = Building::query()->findOrFail($buildingId);
        $this->editingBuildingId = $building->id;
        $this->edit_building_name = $building->name;
        $this->edit_building_address = (string) ($building->address ?? '');
        $this->resetValidation();
        $this->dispatch('open-modal', 'edit-building');
    }

    public function cancelEditBuilding(): void
    {
        $this->editingBuildingId = null;
        $this->reset(['edit_building_name', 'edit_building_address']);
        $this->resetValidation();
        $this->dispatch('close-modal', 'edit-building');
    }

    public function saveBuilding(): void
    {
        if ($this->editingBuildingId === null) {
            return;
        }

        $building = Building::query()->findOrFail($this->editingBuildingId);

        $this->validate([
            'edit_building_name' => ['required', 'string', 'max:255'],
            'edit_building_address' => ['nullable', 'string', 'max:255'],
        ]);

        $building->update([
            'name' => $this->edit_building_name,
            'address' => $this->edit_building_address !== '' ? $this->edit_building_address : null,
        ]);

        session()->flash('mgr_ok', __('Дом обновлён.'));
        $this->cancelEditBuilding();
    }

    public function deleteBuilding(int $buildingId): void
    {
        $building = Building::query()->findOrFail($buildingId);
        $building->delete();

        if ((int) $this->building_id === $buildingId) {
            $this->building_id = Building::query()->orderBy('id')->value('id');
            if ($this->building_id) {
                app(ManagerContext::class)->setBuildingId((int) $this->building_id);
            }
        }

        $this->cancelAllForms();
        session()->flash('mgr_ok', __('Дом удалён.'));
    }

    public function startCreateApartment(): void
    {
        if (! $this->building_id || ! $this->inBuilding) {
            return;
        }

        $this->creatingApartment = true;
        $this->reset([
            'new_apartment_number',
            'new_apartment_area_m2',
            'resident_first_name',
            'resident_last_name',
            'resident_phone',
            'resident_email',
        ]);
        $this->resetValidation();
        $this->dispatch('open-modal', 'create-apartment');
    }

    public function cancelCreateApartment(): void
    {
        $this->creatingApartment = false;
        $this->reset([
            'new_apartment_number',
            'new_apartment_area_m2',
            'resident_first_name',
            'resident_last_name',
            'resident_phone',
            'resident_email',
        ]);
        $this->resetValidation();
        $this->dispatch('close-modal', 'create-apartment');
    }

    public function createApartment(): void
    {
        $this->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'new_apartment_number' => [
                'required',
                'string',
                'max:16',
                Rule::unique('apartments', 'number')->where(fn ($q) => $q->where('building_id', $this->building_id)),
            ],
            'new_apartment_area_m2' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'resident_first_name' => ['required', 'string', 'max:100'],
            'resident_last_name' => ['required', 'string', 'max:100'],
            'resident_phone' => ['nullable', 'string', 'max:32'],
            'resident_email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ], [], [
            'new_apartment_area_m2' => __('Площадь, м²'),
            'resident_first_name' => __('Имя'),
            'resident_last_name' => __('Фамилия'),
            'resident_phone' => __('Телефон'),
            'resident_email' => __('Почта'),
        ]);

        $apartment = Apartment::query()->create([
            'building_id' => $this->building_id,
            'number' => $this->new_apartment_number,
            'area_m2' => $this->new_apartment_area_m2 !== '' ? $this->new_apartment_area_m2 : null,
        ]);

        $user = $this->createResidentUser($apartment);
        $this->sendResidentInvitation($user);

        $this->cancelCreateApartment();
        session()->flash('mgr_ok', __('Квартира и жилец добавлены.'));
    }

    public function startEditApartment(int $apartmentId): void
    {
        $apartment = $this->findApartmentInBuilding($apartmentId);
        $this->editingApartmentId = $apartment->id;
        $this->edit_apartment_number = $apartment->number;
        $this->edit_apartment_area_m2 = $apartment->area_m2 !== null ? (string) $apartment->area_m2 : '';
        $this->resetValidation();
        $this->dispatch('open-modal', 'edit-apartment');
    }

    public function cancelEditApartment(): void
    {
        $this->editingApartmentId = null;
        $this->reset(['edit_apartment_number', 'edit_apartment_area_m2']);
        $this->resetValidation();
        $this->dispatch('close-modal', 'edit-apartment');
    }

    public function saveApartment(): void
    {
        if ($this->editingApartmentId === null || ! $this->building_id) {
            return;
        }

        $apartment = $this->findApartmentInBuilding($this->editingApartmentId);

        $this->validate([
            'edit_apartment_number' => [
                'required',
                'string',
                'max:16',
                Rule::unique('apartments', 'number')
                    ->where(fn ($q) => $q->where('building_id', $this->building_id))
                    ->ignore($apartment->id),
            ],
            'edit_apartment_area_m2' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
        ], [], [
            'edit_apartment_area_m2' => __('Площадь, м²'),
        ]);

        $apartment->update([
            'number' => $this->edit_apartment_number,
            'area_m2' => $this->edit_apartment_area_m2 !== '' ? $this->edit_apartment_area_m2 : null,
        ]);
        session()->flash('mgr_ok', __('Квартира обновлена.'));
        $this->cancelEditApartment();
    }

    public function deleteApartment(int $apartmentId): void
    {
        $apartment = $this->findApartmentInBuilding($apartmentId);

        if ($apartment->users()->where('role', UserRole::Resident)->exists()) {
            session()->flash('mgr_err', __('Нельзя удалить квартиру с жильцом. Сначала удалите жильца.'));

            return;
        }

        $apartment->delete();
        session()->flash('mgr_ok', __('Квартира удалена.'));
    }

    public function startCreateResident(int $apartmentId): void
    {
        $this->findApartmentInBuilding($apartmentId);
        $this->creatingResidentForApartmentId = $apartmentId;
        $this->reset(['resident_first_name', 'resident_last_name', 'resident_phone', 'resident_email']);
        $this->resetValidation();
        $this->dispatch('open-modal', 'create-resident');
    }

    public function cancelCreateResident(): void
    {
        $this->creatingResidentForApartmentId = null;
        $this->reset(['resident_first_name', 'resident_last_name', 'resident_phone', 'resident_email']);
        $this->resetValidation();
        $this->dispatch('close-modal', 'create-resident');
    }

    public function createResident(): void
    {
        if ($this->creatingResidentForApartmentId === null) {
            return;
        }

        $apartment = $this->findApartmentInBuilding($this->creatingResidentForApartmentId);

        if ($apartment->users()->where('role', UserRole::Resident)->exists()) {
            session()->flash('mgr_err', __('В этой квартире уже есть жилец.'));

            return;
        }

        $this->validate([
            'resident_first_name' => ['required', 'string', 'max:100'],
            'resident_last_name' => ['required', 'string', 'max:100'],
            'resident_phone' => ['nullable', 'string', 'max:32'],
            'resident_email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ], [], [
            'resident_first_name' => __('Имя'),
            'resident_last_name' => __('Фамилия'),
            'resident_phone' => __('Телефон'),
            'resident_email' => __('Почта'),
        ]);

        $user = $this->createResidentUser($apartment);
        $this->sendResidentInvitation($user);

        session()->flash('mgr_ok', __('Жилец добавлен. На :email отправлена ссылка для пароля.', ['email' => $user->email]));
        $this->cancelCreateResident();
    }

    protected function createResidentUser(Apartment $apartment): User
    {
        $fullName = trim($this->resident_last_name.' '.$this->resident_first_name);

        return User::query()->create([
            'name' => $fullName,
            'first_name' => $this->resident_first_name,
            'last_name' => $this->resident_last_name,
            'phone' => $this->resident_phone !== '' ? $this->resident_phone : null,
            'email' => $this->resident_email,
            'password' => Hash::make(Str::password(64)),
            'role' => UserRole::Resident,
            'apartment_id' => $apartment->id,
            'email_verified_at' => now(),
        ]);
    }

    protected function sendResidentInvitation(User $user): void
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $user->forceFill(['invitation_sent_at' => now()])->save();

            return;
        }

        if (is_string($status)) {
            session()->flash('mgr_warn', __($status));
        }
    }

    public function startEditResident(int $userId): void
    {
        $user = $this->findResidentInBuilding($userId);
        $user->load('apartment');

        $this->editingResidentId = $user->id;
        $this->editingApartmentId = $user->apartment?->id;
        $this->edit_apartment_number = (string) ($user->apartment?->number ?? '');
        $this->edit_apartment_area_m2 = $user->apartment?->area_m2 !== null ? (string) $user->apartment->area_m2 : '';
        $this->edit_resident_first_name = (string) ($user->first_name ?? '');
        $this->edit_resident_last_name = (string) ($user->last_name ?? '');
        $this->edit_resident_phone = (string) ($user->phone ?? '');
        $this->edit_resident_email = (string) $user->email;
        $this->resetValidation();
        $this->dispatch('open-modal', 'edit-resident');
    }

    public function cancelEditResident(): void
    {
        $this->editingResidentId = null;
        $this->editingApartmentId = null;
        $this->reset([
            'edit_apartment_number',
            'edit_apartment_area_m2',
            'edit_resident_first_name',
            'edit_resident_last_name',
            'edit_resident_phone',
            'edit_resident_email',
        ]);
        $this->resetValidation();
        $this->dispatch('close-modal', 'edit-resident');
    }

    public function saveResident(): void
    {
        if ($this->editingResidentId === null) {
            return;
        }

        $user = $this->findResidentInBuilding($this->editingResidentId);
        $user->load('apartment');
        $apartment = $user->apartment;

        $rules = [
            'edit_resident_first_name' => ['required', 'string', 'max:100'],
            'edit_resident_last_name' => ['required', 'string', 'max:100'],
            'edit_resident_phone' => ['nullable', 'string', 'max:32'],
            'edit_resident_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ];

        if ($apartment) {
            $rules['edit_apartment_number'] = [
                'required',
                'string',
                'max:16',
                Rule::unique('apartments', 'number')
                    ->where(fn ($q) => $q->where('building_id', $this->building_id))
                    ->ignore($apartment->id),
            ];
            $rules['edit_apartment_area_m2'] = ['nullable', 'numeric', 'min:0', 'max:9999.99'];
        }

        $this->validate($rules, [], [
            'edit_resident_first_name' => __('Имя'),
            'edit_resident_last_name' => __('Фамилия'),
            'edit_resident_phone' => __('Телефон'),
            'edit_resident_email' => __('Почта'),
            'edit_apartment_number' => __('Номер квартиры'),
            'edit_apartment_area_m2' => __('Площадь, м²'),
        ]);

        if ($apartment) {
            $apartment->update([
                'number' => $this->edit_apartment_number,
                'area_m2' => $this->edit_apartment_area_m2 !== '' ? $this->edit_apartment_area_m2 : null,
            ]);
        }

        $user->forceFill([
            'first_name' => $this->edit_resident_first_name,
            'last_name' => $this->edit_resident_last_name,
            'name' => trim($this->edit_resident_last_name.' '.$this->edit_resident_first_name),
            'phone' => $this->edit_resident_phone !== '' ? $this->edit_resident_phone : null,
            'email' => $this->edit_resident_email,
        ])->save();

        session()->flash('mgr_ok', __('Данные обновлены.'));
        $this->cancelEditResident();
    }

    public function deleteResident(int $userId): void
    {
        $user = $this->findResidentInBuilding($userId);
        $user->delete();
        session()->flash('mgr_ok', __('Жилец удалён.'));
    }

    public function sendInvitation(int $userId): void
    {
        $user = $this->findResidentInBuilding($userId);
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            session()->flash('mgr_err', is_string($status) ? __($status) : __('Не удалось отправить письмо.'));

            return;
        }

        $user->forceFill(['invitation_sent_at' => now()])->save();
        session()->flash('mgr_ok', __('Ссылка для пароля отправлена на :email', ['email' => $user->email]));
    }

    public function toggleAccess(int $userId): void
    {
        $user = $this->findResidentInBuilding($userId);
        $user->access_suspended_at = $user->access_suspended_at ? null : now();
        $user->save();
        session()->flash('mgr_ok', $user->access_suspended_at ? __('Доступ закрыт.') : __('Доступ открыт.'));
    }

    protected function findApartmentInBuilding(int $apartmentId): Apartment
    {
        if (! $this->building_id) {
            abort(403);
        }

        return Apartment::query()
            ->where('building_id', $this->building_id)
            ->whereKey($apartmentId)
            ->firstOrFail();
    }

    protected function findResidentInBuilding(int $userId): User
    {
        $user = User::query()->where('role', UserRole::Resident)->findOrFail($userId);

        if ((int) $user->apartment?->building_id !== (int) $this->building_id) {
            abort(403);
        }

        return $user;
    }

    protected function cancelAllForms(): void
    {
        $this->cancelEditBuilding();
        $this->cancelEditApartment();
        $this->cancelCreateApartment();
        $this->cancelEditResident();
        $this->cancelCreateResident();
    }

    #[Computed]
    public function buildings(): Collection
    {
        return Building::query()
            ->withCount('apartments')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedBuilding(): ?Building
    {
        if (! $this->building_id) {
            return null;
        }

        return $this->buildings->firstWhere('id', $this->building_id);
    }

    public function getApartmentsProperty(): LengthAwarePaginator
    {
        if (! $this->building_id || ! $this->inBuilding) {
            return Apartment::query()->whereRaw('0 = 1')->paginate(15);
        }

        $sub = DB::table('users')
            ->select('apartment_id', DB::raw('MIN(id) as resident_id'))
            ->where('role', UserRole::Resident->value)
            ->whereNotNull('apartment_id')
            ->groupBy('apartment_id');

        $query = Apartment::query()
            ->where('apartments.building_id', $this->building_id)
            ->leftJoinSub($sub, 'pr', 'pr.apartment_id', '=', 'apartments.id')
            ->leftJoin('users', 'users.id', '=', 'pr.resident_id')
            ->select('apartments.*')
            ->with(['users' => fn ($q) => $q->where('role', UserRole::Resident)]);

        if ($this->search !== '') {
            $s = '%'.addcslashes($this->search, '%_\\').'%';
            $query->where(function ($q) use ($s) {
                $q->where('apartments.number', 'like', $s)
                    ->orWhere('users.first_name', 'like', $s)
                    ->orWhere('users.last_name', 'like', $s)
                    ->orWhere('users.name', 'like', $s)
                    ->orWhere('users.email', 'like', $s)
                    ->orWhere('users.phone', 'like', $s);
            });
        }

        $dir = $this->sortAsc ? 'asc' : 'desc';

        match ($this->sortField) {
            'first_name' => $query->orderBy('users.first_name', $dir)->orderBy('users.last_name', $dir)->orderBy('apartments.number'),
            'last_name' => $query->orderBy('users.last_name', $dir)->orderBy('users.first_name', $dir)->orderBy('apartments.number'),
            'email' => $query->orderBy('users.email', $dir)->orderBy('apartments.number'),
            'phone' => $query->orderBy('users.phone', $dir)->orderBy('apartments.number'),
            default => $query->orderBy('apartments.number', $dir),
        };

        return $query->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.manager.household-panel');
    }
}
