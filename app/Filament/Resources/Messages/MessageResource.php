<?php

namespace App\Filament\Resources\Messages;

use App\Filament\Resources\Messages\Pages\ManageMessages;
use App\Models\Message;
use App\Services\GeminiReplyException;
use App\Services\GeminiReplyService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static ?string $navigationLabel = 'Messages';

    protected static ?string $modelLabel = 'message';

    protected static ?string $pluralModelLabel = 'messages';

    protected static string|\UnitEnum|null $navigationGroup = 'Communication';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sender_name')->label('Nom')->disabled(),
                TextInput::make('sender_email')->label('E-mail')->disabled(),
                TextInput::make('subject')->label('Objet')->disabled(),
                TextInput::make('source')->label('Source')->disabled(),
                TextInput::make('conversation_id')->label('Téléphone / conversation')->disabled(),
                Textarea::make('body')
                    ->label('Message reçu')
                    ->rows(6)
                    ->disabled()
                    ->columnSpanFull(),
                Textarea::make('reply')
                    ->label('Réponse générée par Gemini')
                    ->rows(6)
                    ->columnSpanFull(),
                TextInput::make('status')->label('Statut')->disabled(),
                TextInput::make('reply_error')
                    ->label('Erreur de réponse')
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sender_name')->label('Nom')->searchable(),
                TextColumn::make('sender_email')->label('E-mail')->searchable(),
                TextColumn::make('subject')->label('Objet')->searchable(),
                TextColumn::make('conversation_id')->label('Téléphone / conversation')->searchable(),
                TextColumn::make('source')->label('Source'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Message::STATUS_REPLIED => 'success',
                        Message::STATUS_FAILED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')->label('Reçu le')->dateTime()->sortable(),
            ])
            ->filters([])
            ->recordActions([
                Action::make('retryAiReply')
                    ->label('Réessayer avec l’IA')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (Message $record): bool => $record->status !== Message::STATUS_REPLIED)
                    ->action(function (Message $record): void {
                        try {
                            app(GeminiReplyService::class)->reply($record);

                            Notification::make()
                                ->title('Réponse générée')
                                ->success()
                                ->send();
                        } catch (GeminiReplyException $exception) {
                            Log::error('Gemini reply retry failed.', [
                                'message_id' => $record->getKey(),
                                'exception' => $exception,
                            ]);

                            Notification::make()
                                ->title('La réponse automatique a échoué')
                                ->body('Vérifiez la configuration Gemini puis réessayez.')
                                ->danger()
                                ->send();
                        }
                    }),
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
            'index' => ManageMessages::route('/'),
        ];
    }
}
