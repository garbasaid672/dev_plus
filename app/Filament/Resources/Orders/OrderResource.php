<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'Commandes';

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes';

    protected static string|\UnitEnum|null $navigationGroup = 'Ventes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->label('N° de commande')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'processing' => 'En préparation',
                        'shipped' => 'Expédiée',
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                    ])
                    ->default('pending')
                    ->required(),
                TextInput::make('customer_name')
                    ->label('Nom du client')
                    ->required()
                    ->maxLength(255),
                TextInput::make('customer_email')
                    ->label('E-mail')
                    ->email()
                    ->maxLength(255),
                TextInput::make('customer_phone')
                    ->label('Téléphone')
                    ->tel()
                    ->maxLength(50),
                Textarea::make('shipping_address')
                    ->label('Adresse de livraison')
                    ->columnSpanFull(),
                Textarea::make('items')
                    ->label('Articles commandés')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
                TextInput::make('total_amount')
                    ->label('Montant total')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->default(0),
                Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->label('N°')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Client')->searchable()->sortable(),
                TextColumn::make('customer_email')->label('E-mail')->searchable(),
                TextColumn::make('total_amount')->label('Total')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed', 'shipped' => 'success',
                        'cancelled' => 'danger',
                        'processing' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->label('Créée le')->dateTime()->sortable(),
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
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrders::route('/'),
        ];
    }
}
