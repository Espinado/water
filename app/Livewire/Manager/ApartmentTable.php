<?php

namespace App\Livewire\Manager;

use App\Enums\UserRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ApartmentTable extends Component
{
    use WithPagination;

    public ?int $building_id = null;

    public string $search = '';

    public string $sortField = 'number';

    public bool $sortAsc = true;

    public int $statusYear = 0;

    public int $statusMonth = 0;

    public function mount(): void
    {
        $this->building_id = Building::query()->orderBy('id')->value('id');
        $this->statusYear = (int) now()->year;
        $this->statusMonth = (int) now()->month;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedBuildingId(): void
    {
        $this->resetPage();
    }

    public function updatedStatusYear(): void
    {
        $this->resetPage();
    }

    public function updatedStatusMonth(): void
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

    public function sendInvitation(int $userId): void
    {
        $user = User::query()->where('role', UserRole::Resident)->findOrFail($userId);

        if ((int) $user->apartment?->building_id !== (int) $this->building_id) {
            abort(403);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            session()->flash('apt_err', is_string($status) ? __($status) : 'Не удалось отправить письмо.');

            return;
        }

        $user->forceFill(['invitation_sent_at' => now()])->save();
        session()->flash('apt_ok', 'Ссылка для установки или сброса пароля отправлена на '.$user->email);
    }

    public function toggleAccess(int $userId): void
    {
        $user = User::query()->where('role', UserRole::Resident)->findOrFail($userId);

        if ((int) $user->apartment?->building_id !== (int) $this->building_id) {
            abort(403);
        }

        $user->access_suspended_at = $user->access_suspended_at ? null : now();
        $user->save();

        session()->flash('apt_ok', $user->access_suspended_at ? 'Доступ закрыт.' : 'Доступ открыт.');
    }

    public function residentDisplayFirst(Model $apt): string
    {
        [$first] = $this->residentNameParts($apt);

        return $first;
    }

    public function residentDisplayLast(Model $apt): string
    {
        [, $last] = $this->residentNameParts($apt);

        return $last;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function residentNameParts(Model $apt): array
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

    public function formatInvitationDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return Carbon::parse($value)->format('d.m.Y');
    }

    #[Computed]
    public function buildings(): Collection
    {
        return Building::query()->withCount('apartments')->orderBy('name')->get();
    }

    public function getRowsProperty(): LengthAwarePaginator
    {
        if (! $this->building_id) {
            return Apartment::query()->whereRaw('0 = 1')->paginate(15);
        }

        $sub = DB::table('users')
            ->select('apartment_id', DB::raw('MIN(id) as resident_id'))
            ->where('role', UserRole::Resident->value)
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
            ->leftJoin('users', 'users.id', '=', 'pr.resident_id')
            ->select('apartments.*')
            ->addSelect([
                'mr_p.id as period_meter_reading_id',
                'users.id as resident_user_id',
                'users.first_name as ru_first_name',
                'users.last_name as ru_last_name',
                'users.name as ru_name',
                'users.email as ru_email',
                'users.phone as ru_phone',
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

        $dir = $this->sortAsc ? 'asc' : 'desc';

        switch ($this->sortField) {
            case 'first_name':
                $query->orderBy('users.first_name', $dir)->orderBy('users.last_name', $dir);
                break;
            case 'last_name':
                $query->orderBy('users.last_name', $dir)->orderBy('users.first_name', $dir);
                break;
            case 'email':
                $query->orderBy('users.email', $dir);
                break;
            case 'phone':
                $query->orderBy('users.phone', $dir);
                break;
            default:
                $query->orderBy('apartments.number', $dir);
                break;
        }

        return $query->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.manager.apartment-table');
    }
}
