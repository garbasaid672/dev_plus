<?php

namespace Tests\Feature;

use App\Filament\Pages\Comptes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ComptesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_existing_accounts(): void
    {
        $admin = User::factory()->create();
        $account = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(Comptes::class)
            ->assertCanSeeTableRecords([$admin, $account])
            ->assertSee($account->name)
            ->assertSee($account->email);
    }

    public function test_admin_can_create_an_account_with_a_hashed_password(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(Comptes::class)
            ->callTableAction('create', data: [
                'name' => 'Nouveau compte',
                'email' => 'nouveau@example.com',
                'password' => 'safe-password-123',
            ])
            ->assertHasNoTableActionErrors();

        $account = User::query()->where('email', 'nouveau@example.com')->sole();

        $this->assertSame('Nouveau compte', $account->name);
        $this->assertTrue(Hash::check('safe-password-123', $account->password));
    }

    public function test_admin_can_edit_an_account_without_replacing_its_password(): void
    {
        $admin = User::factory()->create();
        $account = User::factory()->create([
            'password' => 'original-password',
        ]);
        $originalPassword = $account->password;

        Livewire::actingAs($admin)
            ->test(Comptes::class)
            ->callTableAction('edit', $account, [
                'name' => 'Nom modifié',
                'email' => $account->email,
                'password' => null,
            ])
            ->assertHasNoTableActionErrors();

        $account->refresh();

        $this->assertSame('Nom modifié', $account->name);
        $this->assertSame($originalPassword, $account->password);
    }
}
