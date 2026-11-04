<?php

namespace Tests\Feature;

use App\Filament\Pages\Parametres;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ParametresPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_parameters_page_from_the_panel(): void
    {
        $admin = User::factory()->create();

        config(['app.env' => 'local']);

        $response = $this->actingAs($admin)->get('/admin/profile');

        $response->assertOk()
            ->assertSee('Paramètres');
    }

    public function test_profile_form_labels_and_actions_are_displayed_in_french(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(Parametres::class)
            ->assertSee('Nom')
            ->assertSee('Adresse e-mail')
            ->assertSee('Nouveau mot de passe')
            ->assertSee('Enregistrer les modifications')
            ->assertSee('Annuler')
            ->assertDontSee('Email address')
            ->assertDontSee('Save changes');
    }

    public function test_parameters_page_is_available_in_the_admin_navigation(): void
    {
        Filament::setCurrentPanel('admin');

        $item = collect(Filament::getNavigationItems())
            ->first(fn ($item): bool => $item->getLabel() === 'Paramètres');

        $this->assertNotNull($item);
        $this->assertSame('Gestion', $item->getGroup());
        $this->assertSame(route('filament.admin.auth.profile'), $item->getUrl());
    }

    public function test_admin_can_update_their_name_from_parameters(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(Parametres::class)
            ->fillForm([
                'name' => 'Nom mis à jour',
                'email' => $admin->email,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Nom mis à jour', $admin->refresh()->name);
    }

    public function test_admin_must_provide_the_current_password_before_changing_their_password(): void
    {
        $admin = User::factory()->create([
            'password' => 'current-password',
        ]);
        $originalPassword = $admin->password;

        Livewire::actingAs($admin)
            ->test(Parametres::class)
            ->fillForm([
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => 'new-password-123',
                'passwordConfirmation' => 'new-password-123',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        $this->assertSame($originalPassword, $admin->refresh()->password);
    }

    public function test_admin_can_change_their_password_after_confirming_the_current_one(): void
    {
        $admin = User::factory()->create([
            'password' => 'current-password',
        ]);

        Livewire::actingAs($admin)
            ->test(Parametres::class)
            ->fillForm([
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => 'new-password-123',
                'passwordConfirmation' => 'new-password-123',
                'currentPassword' => 'current-password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('new-password-123', $admin->refresh()->password));
    }
}
