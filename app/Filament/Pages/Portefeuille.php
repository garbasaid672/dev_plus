<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\User;
use App\Services\WithdrawalService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class Portefeuille extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Wallet';

    protected static ?string $title = 'Wallet';

    protected static string|\UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?int $navigationSort = 2;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected string $view = 'filament.pages.portefeuille';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('withdraw')
                ->label('Retirer')
                ->icon(Heroicon::OutlinedBanknotes)
                ->modalHeading('Demande de retrait')
                ->modalDescription(fn (WithdrawalService $withdrawalService): string => 'Solde disponible : '
                    .number_format($withdrawalService->getAvailableBalance(), 0, ',', ' ').' FCFA')
                ->form([
                    TextInput::make('amount')
                        ->label('Montant (FCFA)')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1000),
                    Select::make('method')
                        ->label('Opérateur')
                        ->options(WithdrawalService::METHODS)
                        ->required(),
                    TextInput::make('account_details')
                        ->label('Numéro de paiement')
                        ->tel()
                        ->required(),
                ])
                ->action(function (array $data, WithdrawalService $withdrawalService): void {
                    $user = auth()->user();

                    if (! $user instanceof User) {
                        abort(401);
                    }

                    $withdrawalService->create(
                        $user,
                        (int) $data['amount'],
                        $data['method'],
                        $data['account_details'],
                    );

                    Notification::make()
                        ->title('Demande de retrait envoyée')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query())
            ->columns([
                TextColumn::make('order_number')
                    ->label('N° de commande')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('items')
                    ->label('Achats')
                    ->wrap()
                    ->limit(80),
                TextColumn::make('customer_name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Montant')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' FCFA')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed', 'shipped' => 'success',
                        'cancelled' => 'danger',
                        'processing' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'processing' => 'En préparation',
                        'shipped' => 'Expédiée',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Aucun achat')
            ->emptyStateDescription('Les commandes apparaîtront ici dès leur enregistrement.');
    }

    protected function getViewData(): array
    {
        return [
            'confirmedRevenue' => (float) Order::query()
                ->where('status', 'completed')
                ->sum('total_amount'),
            'purchaseTotal' => (float) Order::query()
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount'),
            'orderCount' => Order::query()->count(),
        ];
    }
}
