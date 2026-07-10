<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Manager\ManagerTeam;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_open_team_page(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get('/manager/team')
            ->assertOk();
    }

    public function test_manager_can_create_manager_and_send_invitation(): void
    {
        Notification::fake();

        $manager = User::factory()->manager()->create([
            'first_name' => 'Anna',
            'last_name' => 'Admin',
        ]);

        Livewire::actingAs($manager)
            ->test(ManagerTeam::class)
            ->set('new_first_name', 'Jānis')
            ->set('new_last_name', 'Ozols')
            ->set('new_email', 'janis.manager@example.com')
            ->set('new_phone', '+37120000099')
            ->call('createManager')
            ->assertHasNoErrors()
            ->assertSee('Приглашение отправлено');

        $created = User::query()->where('email', 'janis.manager@example.com')->first();

        $this->assertNotNull($created);
        $this->assertSame(UserRole::Manager, $created->role);
        $this->assertSame('Jānis', $created->first_name);
        $this->assertSame('Ozols', $created->last_name);
        $this->assertSame('+37120000099', $created->phone);
        $this->assertNotNull($created->invitation_sent_at);

        Notification::assertSentTo($created, ResetPassword::class);
    }

    public function test_manager_can_update_suspend_and_delete_other_manager(): void
    {
        Notification::fake();

        $manager = User::factory()->manager()->create();
        $other = User::factory()->manager()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'other@example.com',
            'phone' => '+37120000001',
        ]);

        Livewire::actingAs($manager)
            ->test(ManagerTeam::class)
            ->call('startEdit', $other->id)
            ->set('edit_first_name', 'New')
            ->set('edit_last_name', 'Manager')
            ->set('edit_email', 'new.manager@example.com')
            ->set('edit_phone', '+37120000002')
            ->call('saveManager')
            ->assertHasNoErrors()
            ->assertSee('Данные управляющего обновлены');

        $other->refresh();
        $this->assertSame('New', $other->first_name);
        $this->assertSame('new.manager@example.com', $other->email);

        Livewire::actingAs($manager)
            ->test(ManagerTeam::class)
            ->call('suspendManager', $other->id)
            ->assertSee('Доступ управляющего отключён');

        $other->refresh();
        $this->assertNotNull($other->access_suspended_at);

        Livewire::actingAs($manager)
            ->test(ManagerTeam::class)
            ->call('restoreManager', $other->id)
            ->assertSee('Доступ управляющего восстановлен');

        $other->refresh();
        $this->assertNull($other->access_suspended_at);

        Livewire::actingAs($manager)
            ->test(ManagerTeam::class)
            ->call('resendInvitation', $other->id)
            ->assertSee('Приглашение повторно отправлено');

        Notification::assertSentTo($other, ResetPassword::class);

        Livewire::actingAs($manager)
            ->test(ManagerTeam::class)
            ->call('deleteManager', $other->id)
            ->assertSee('Управляющий удалён');

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_manager_cannot_delete_or_suspend_self(): void
    {
        $manager = User::factory()->manager()->create();

        Livewire::actingAs($manager)
            ->test(ManagerTeam::class)
            ->call('suspendManager', $manager->id)
            ->assertSee('Нельзя отключить собственный доступ');

        Livewire::actingAs($manager)
            ->test(ManagerTeam::class)
            ->call('deleteManager', $manager->id)
            ->assertSee('Нельзя удалить собственную учётную запись');

        $this->assertDatabaseHas('users', ['id' => $manager->id]);
        $this->assertNull($manager->fresh()->access_suspended_at);
    }
}
