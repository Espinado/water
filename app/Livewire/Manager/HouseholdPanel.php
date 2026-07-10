<?php

namespace App\Livewire\Manager;

use App\Enums\UserRole;
use App\Livewire\Concerns\HasManagerContext;
use App\Livewire\Concerns\NormalizesDecimalInput;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use App\Services\LinkUserToApartment;
use App\Services\ManagerContext;
use App\Services\MeterReadingSubmissionNotifier;
use App\Services\SendUserInvitation;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.manager')]
class HouseholdPanel extends Component
{
    use HasManagerContext;
    use NormalizesDecimalInput;
    use WithPagination;

    /** @var list<string> */
    protected array $decimalInputProperties = ['new_apartment_area_m2', 'edit_apartment_area_m2'];

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

    public int $statusYear = 0;

    public int $statusMonth = 0;

    #[Url(as: 'filter', except: 'all', history: true)]
    public string $statusFilter = 'all';

    public function mount(ManagerContext $context): void
    {
        $this->loadManagerContext($context);

        if (! in_array($this->statusFilter, ['all', 'debt', 'submitted', 'no_login', 'no_resident'], true)) {
            $this->statusFilter = 'all';
        }

        if ($this->building_id) {
            $this->inBuilding = true;
        }
    }

    protected function syncPeriodFromContext(ManagerContext $context): void
    {
        $this->statusYear = $context->year();
        $this->statusMonth = $context->month();
    }

    protected function managerPeriodYear(): int
    {
        return $this->statusYear;
    }

    protected function managerPeriodMonth(): int
    {
        return $this->statusMonth;
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusYear(ManagerContext $context): void
    {
        $this->persistManagerContext($context);
        $this->resetPage();
    }

    public function updatedStatusMonth(ManagerContext $context): void
    {
        $this->persistManagerContext($context);
        $this->resetPage();
    }

    public function pollSubmissionUpdates(MeterReadingSubmissionNotifier $notifier): void
    {
        if (! $this->building_id || ! $this->inBuilding) {
            return;
        }

        $events = $notifier->pullForBuildingPeriod(
            (int) $this->building_id,
            $this->statusYear,
            $this->statusMonth,
        );

        foreach ($events as $event) {
            $this->dispatch(
                'manager-submission-toast',
                message: __('Квартира № :number сдала показания', [
                    'number' => $event['apartment_number'],
                ]),
            );
        }

        $this->skipRender();
    }

    public function formatInvitationDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return Carbon::parse($value)->format('d.m.Y');
    }

    public function occupantDisplayFirst(Model $apt): string
    {
        [$first] = $this->occupantNameParts($apt);

        return $first;
    }

    public function occupantDisplayLast(Model $apt): string
    {
        [, $last] = $this->occupantNameParts($apt);

        return $last;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function occupantNameParts(Model $apt): array
    {
        $fn = $apt->getAttribute('ru_first_name');
        $ln = $apt->getAttribute('ru_last_name');
        $name = $apt->getAttribute('ru_name');

        if (($fn === null || $fn === '') && ($ln === null || $ln === '') && $name) {
            $p = preg_split('/\s+/u', trim((string) $name), 2, PREG_SPLIT_NO_EMPTY);
            $fn = $p[0] ?? '';
            $ln = $p[1] ?? '';
        }

        $first = ($fn !== null && $fn !== '') ? (string) $fn : '—';
        $last = ($ln !== null && $ln !== '') ? (string) $ln : '—';

        return [$first, $last];
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
            'resident_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->where('role', UserRole::Resident->value)],
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

        $user = $this->createOccupantForApartment($apartment);
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

        if ($apartment->users()->where('role', UserRole::Manager)->whereNotNull('apartment_id')->exists()) {
            session()->flash('mgr_err', __('Нельзя удалить квартиру с привязанным управляющим. Сначала отвяжите его от квартиры.'));

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

        if ($apartment->isOccupiedByOther()) {
            session()->flash('mgr_err', __('В этой квартире уже есть жилец.'));

            return;
        }

        $this->validate([
            'resident_first_name' => ['required', 'string', 'max:100'],
            'resident_last_name' => ['required', 'string', 'max:100'],
            'resident_phone' => ['nullable', 'string', 'max:32'],
            'resident_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->where('role', UserRole::Resident->value)],
        ], [], [
            'resident_first_name' => __('Имя'),
            'resident_last_name' => __('Фамилия'),
            'resident_phone' => __('Телефон'),
            'resident_email' => __('Почта'),
        ]);

        $user = $this->createOccupantForApartment($apartment);

        if (! $user->wasRecentlyCreated) {
            session()->flash('mgr_ok', __('Управляющий :email привязан к квартире. Тот же email и пароль — для входа в приложение жильца.', ['email' => $user->email]));
        } else {
            $this->sendResidentInvitation($user);
            session()->flash('mgr_ok', __('Жилец добавлен. На :email отправлена ссылка для пароля.', ['email' => $user->email]));
        }

        $this->cancelCreateResident();
    }

    protected function createOccupantForApartment(Apartment $apartment): User
    {
        $linker = app(LinkUserToApartment::class);
        $existing = $linker->findByEmail($this->resident_email);

        if ($existing !== null) {
            if ($existing->isResident()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'resident_email' => __('Этот email уже используется жильцом.'),
                ]);
            }

            if ($existing->isManager()) {
                $linker->link($existing, $apartment, [
                    'first_name' => $this->resident_first_name,
                    'last_name' => $this->resident_last_name,
                    'phone' => $this->resident_phone !== '' ? $this->resident_phone : null,
                ]);

                return $existing;
            }
        }

        return $linker->createResident($apartment, [
            'first_name' => $this->resident_first_name,
            'last_name' => $this->resident_last_name,
            'phone' => $this->resident_phone !== '' ? $this->resident_phone : null,
            'email' => $this->resident_email,
        ]);
    }

    protected function createResidentUser(Apartment $apartment): User
    {
        return $this->createOccupantForApartment($apartment);
    }

    protected function sendResidentInvitation(User $user, ?SendUserInvitation $invitations = null): void
    {
        $invitations ??= app(SendUserInvitation::class);
        $result = $invitations->send($user);

        if (! $result['sent'] && $result['message']) {
            session()->flash('mgr_warn', $result['message']);
        }
    }

    public function startEditResident(int $userId): void
    {
        $user = $this->findOccupantInBuilding($userId);
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

        $user = $this->findOccupantInBuilding($this->editingResidentId);
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
        $user = $this->findOccupantInBuilding($userId);

        if ($user->isManager()) {
            app(LinkUserToApartment::class)->unlink($user);
            session()->flash('mgr_ok', __('Управляющий отвязан от квартиры. Доступ к приложению жильца закрыт.'));

            return;
        }

        $user->delete();
        session()->flash('mgr_ok', __('Жилец удалён.'));
    }

    public function sendInvitation(int $userId, SendUserInvitation $invitations): void
    {
        $user = $this->findOccupantInBuilding($userId);
        $result = $invitations->send($user);

        if ($result['sent']) {
            session()->flash('mgr_ok', __('Ссылка для пароля отправлена на :email', ['email' => $user->email]));
        } else {
            session()->flash('mgr_err', $result['message'] ?? __('Не удалось отправить письмо.'));
        }
    }

    public function toggleAccess(int $userId): void
    {
        $user = $this->findOccupantInBuilding($userId);
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

    protected function findOccupantInBuilding(int $userId): User
    {
        $user = User::query()->findOrFail($userId);

        if ((int) $user->apartment?->building_id !== (int) $this->building_id) {
            abort(403);
        }

        if (! $user->occupiesApartment()) {
            abort(404);
        }

        return $user;
    }

    protected function findResidentInBuilding(int $userId): User
    {
        $user = $this->findOccupantInBuilding($userId);

        if (! $user->isResident()) {
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
            ->select('apartment_id', DB::raw('MIN(id) as occupant_id'))
            ->whereNotNull('apartment_id')
            ->groupBy('apartment_id');

        $query = Apartment::query()
            ->where('apartments.building_id', $this->building_id)
            ->leftJoin('meter_readings as mr_p', function ($j) {
                $j->on('mr_p.apartment_id', '=', 'apartments.id')
                    ->where('mr_p.year', '=', $this->statusYear)
                    ->where('mr_p.month', '=', $this->statusMonth);
            })
            ->leftJoinSub($sub, 'pr', 'pr.apartment_id', '=', 'apartments.id')
            ->leftJoin('users', 'users.id', '=', 'pr.occupant_id')
            ->select('apartments.*')
            ->addSelect([
                'mr_p.id as period_meter_reading_id',
                'users.id as occupant_user_id',
                'users.first_name as ru_first_name',
                'users.last_name as ru_last_name',
                'users.name as ru_name',
                'users.email as ru_email',
                'users.phone as ru_phone',
                'users.role as ru_role',
                'users.invitation_sent_at as ru_invitation_sent_at',
                'users.access_suspended_at as ru_access_suspended_at',
                'users.last_login_at as ru_last_login_at',
            ]);

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

        match ($this->statusFilter) {
            'debt' => $query->whereNull('mr_p.id'),
            'submitted' => $query->whereNotNull('mr_p.id'),
            'no_login' => $query->whereNotNull('users.id')->whereNull('users.last_login_at'),
            'no_resident' => $query->whereNull('users.id'),
            default => null,
        };

        $dir = $this->sortAsc ? 'asc' : 'desc';

        if ($this->sortField === 'number' && $this->statusFilter !== 'submitted') {
            $query->orderByRaw('CASE WHEN mr_p.id IS NULL THEN 0 ELSE 1 END ASC');
        }

        match ($this->sortField) {
            'first_name' => $query->orderBy('users.first_name', $dir)->orderBy('users.last_name', $dir),
            'last_name' => $query->orderBy('users.last_name', $dir)->orderBy('users.first_name', $dir),
            'email' => $query->orderBy('users.email', $dir),
            'phone' => $query->orderBy('users.phone', $dir),
            default => $query->orderBy('apartments.number', $dir),
        };

        return $query->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.manager.household-panel');
    }
}
