<?php

namespace Tests\Feature;

use App\Filament\Pages\Portefeuille;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortefeuillePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_is_available_in_the_sales_navigation(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');

        $item = collect(Filament::getNavigation())
            ->flatMap(fn ($group) => collect($group->getItems()))
            ->first(fn ($item): bool => $item->getLabel() === 'Wallet');

        $this->assertNotNull($item);
        $this->assertSame('Ventes', $item->getGroup());
        $this->assertSame(Portefeuille::getUrl(), $item->getUrl());
    }

    public function test_admin_can_see_purchase_amounts_and_totals_on_the_wallet(): void
    {
        $admin = User::factory()->create();

        $completedOrder = $this->createOrder('CMD-001', 'Article terminé', 30000, 'completed');
        $pendingOrder = $this->createOrder('CMD-002', 'Article en attente', 15000, 'pending');
        $cancelledOrder = $this->createOrder('CMD-003', 'Article annulé', 8000, 'cancelled');

        Livewire::actingAs($admin)
            ->test(Portefeuille::class)
            ->assertCanSeeTableRecords([$completedOrder, $pendingOrder, $cancelledOrder])
            ->assertSee('Revenus confirmés')
            ->assertSee('30 000 FCFA')
            ->assertSee('Total des achats')
            ->assertSee('45 000 FCFA')
            ->assertSee('Commandes enregistrées')
            ->assertSee('3')
            ->assertSee('Article terminé')
            ->assertSee('30,000.00 FCFA')
            ->assertSee('Article en attente')
            ->assertSee('15,000.00 FCFA')
            ->assertSee('Article annulé')
            ->assertSee('8,000.00 FCFA');
    }

    private function createOrder(string $orderNumber, string $items, int $totalAmount, string $status): Order
    {
        return Order::query()->create([
            'order_number' => $orderNumber,
            'customer_name' => 'Client test',
            'items' => $items,
            'total_amount' => $totalAmount,
            'status' => $status,
        ]);
    }
}
