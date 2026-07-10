<?php

namespace App\Livewire\Manager;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\SendUserInvitation;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.manager')]
class ManagerTeam extends Component
{
    public string $new_first_name = '';

    public string $new_last_name = '';

    public string $new_email = '';

    public string $new_phone = '';

    public ?int $editingId = null;

    public string $edit_first_name = '';

    public string $edit_last_name = '';

    public string $edit_email = '';

    public string $edit_phone = '';

    public function createManager(SendUserInvitation $invitations): void
    {
        $this->validate([
            'new_first_name' => ['required', 'string', 'max:100'],
            'new_last_name' => ['required', 'string', 'max:100'],
            'new_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'new_phone' => ['nullable', 'string', 'max:32'],
        ], [], [
            'new_first_name' => __('Имя'),
            'new_last_name' => __('Фамилия'),
            'new_email' => __('Email'),
            'new_phone' => __('Телефон'),
        ]);

        $user = $invitations->createInvitedManager([
            'first_name' => $this->new_first_name,
            'last_name' => $this->new_last_name,
            'email' => $this->new_email,
            'phone' => $this->new_phone !== '' ? $this->new_phone : null,
            'role' => UserRole::Manager,
        ]);

        $result = $invitations->send($user);

        $this->reset(['new_first_name', 'new_last_name', 'new_email', 'new_phone']);
        $this->resetValidation();
        unset($this->managers);

        if ($result['sent']) {
            session()->flash('mgr_ok', __('Управляющий добавлен. Приглашение отправлено на :email.', ['email' => $user->email]));
        } else {
            session()->flash('mgr_warn', $result['message'] ?? __('Управляющий создан, но письмо не отправлено.'));
        }
    }

    public function startEdit(int $managerId): void
    {
        $manager = $this->findManager($managerId);
        $this->editingId = $manager->id;
        $this->edit_first_name = (string) ($manager->first_name ?? '');
        $this->edit_last_name = (string) ($manager->last_name ?? '');
        $this->edit_email = (string) $manager->email;
        $this->edit_phone = (string) ($manager->phone ?? '');
        $this->resetValidation();
        $this->dispatch('open-modal', 'edit-manager');
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->reset(['edit_first_name', 'edit_last_name', 'edit_email', 'edit_phone']);
        $this->resetValidation();
        $this->dispatch('close-modal', 'edit-manager');
    }

    public function saveManager(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $manager = $this->findManager($this->editingId);

        $this->validate([
            'edit_first_name' => ['required', 'string', 'max:100'],
            'edit_last_name' => ['required', 'string', 'max:100'],
            'edit_email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($manager->id)],
            'edit_phone' => ['nullable', 'string', 'max:32'],
        ], [], [
            'edit_first_name' => __('Имя'),
            'edit_last_name' => __('Фамилия'),
            'edit_email' => __('Email'),
            'edit_phone' => __('Телефон'),
        ]);

        $manager->update([
            'first_name' => $this->edit_first_name,
            'last_name' => $this->edit_last_name,
            'name' => trim($this->edit_last_name.' '.$this->edit_first_name),
            'email' => $this->edit_email,
            'phone' => $this->edit_phone !== '' ? $this->edit_phone : null,
        ]);

        unset($this->managers);
        session()->flash('mgr_ok', __('Данные управляющего обновлены.'));
        $this->cancelEdit();
    }

    public function resendInvitation(int $managerId, SendUserInvitation $invitations): void
    {
        $manager = $this->findManager($managerId);
        $result = $invitations->send($manager);

        if ($result['sent']) {
            session()->flash('mgr_ok', __('Приглашение повторно отправлено на :email.', ['email' => $manager->email]));
        } else {
            session()->flash('mgr_err', $result['message'] ?? __('Не удалось отправить приглашение.'));
        }
    }

    public function suspendManager(int $managerId): void
    {
        if ($managerId === auth()->id()) {
            session()->flash('mgr_err', __('Нельзя отключить собственный доступ.'));

            return;
        }

        $this->findManager($managerId)->update(['access_suspended_at' => now()]);
        unset($this->managers);
        session()->flash('mgr_ok', __('Доступ управляющего отключён.'));
    }

    public function restoreManager(int $managerId): void
    {
        $this->findManager($managerId)->update(['access_suspended_at' => null]);
        unset($this->managers);
        session()->flash('mgr_ok', __('Доступ управляющего восстановлен.'));
    }

    public function deleteManager(int $managerId): void
    {
        if ($managerId === auth()->id()) {
            session()->flash('mgr_err', __('Нельзя удалить собственную учётную запись.'));

            return;
        }

        $this->findManager($managerId)->delete();
        unset($this->managers);

        if ($this->editingId === $managerId) {
            $this->cancelEdit();
        }

        session()->flash('mgr_ok', __('Управляющий удалён.'));
    }

    #[Computed]
    public function managers(): Collection
    {
        return User::query()
            ->where('role', UserRole::Manager)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function displayName(User $manager): string
    {
        $full = trim(($manager->last_name ?? '').' '.($manager->first_name ?? ''));

        return $full !== '' ? $full : (string) $manager->name;
    }

    public function statusLabel(User $manager): string
    {
        if ($manager->isAccessSuspended()) {
            return __('Отключён');
        }

        if ($manager->last_login_at === null) {
            return __('Ожидает входа');
        }

        return __('Активен');
    }

    public function statusTone(User $manager): string
    {
        if ($manager->isAccessSuspended()) {
            return 'danger';
        }

        if ($manager->last_login_at === null) {
            return 'warning';
        }

        return 'success';
    }

    public function formatDate(?Carbon $value): string
    {
        if ($value === null) {
            return '—';
        }

        return $value->locale(app()->getLocale())->translatedFormat('d.m.Y');
    }

    protected function findManager(int $managerId): User
    {
        return User::query()
            ->where('role', UserRole::Manager)
            ->findOrFail($managerId);
    }

    public function render(): View
    {
        return view('livewire.manager.manager-team');
    }
}
