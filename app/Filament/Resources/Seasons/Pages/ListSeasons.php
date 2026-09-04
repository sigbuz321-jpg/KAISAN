<?php

namespace App\Filament\Resources\Seasons\Pages;

use App\Actions\ResetSeason;
use App\Filament\Resources\Seasons\SeasonResource;
use App\Models\Season;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSeasons extends ListRecords
{
    protected static string $resource = SeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetMusim')
                ->label('Mulai musim baru')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->visible(fn () => auth()->user()?->can('reset', Season::class) ?? false)
                ->modalHeading('Mulai musim baru?')
                ->modalDescription(
                    'Papan peringkat akan dikosongkan dan dihitung ulang dari nol. '
                    .'Nilai ujian, jawaban, dan level latihan murid TIDAK dihapus dan tetap bisa dilihat.'
                )
                ->modalSubmitActionLabel('Mulai musim baru')
                ->schema([
                    TextInput::make('nama')
                        ->label('Nama musim baru')
                        ->placeholder('Contoh: Semester Genap 2026/2027')
                        ->required()
                        ->maxLength(120),

                    // The second of the two confirmations the skill asks for.
                    // The modal itself is the first; this makes the admin state
                    // what they understand, because this is the action most
                    // often mistaken for deleting results.
                    Checkbox::make('paham')
                        ->label('Saya mengerti peringkat akan dikosongkan, dan nilai ujian tetap tersimpan.')
                        ->accepted()
                        ->required()
                        ->validationMessages([
                            'accepted' => 'Centang dulu untuk melanjutkan.',
                        ]),
                ])
                ->action(function (array $data, ResetSeason $reset) {
                    $season = $reset->handle($data['nama']);

                    Notification::make()
                        ->title("Musim \"{$season->name}\" dimulai.")
                        ->body('Peringkat musim sebelumnya tersimpan sebagai arsip. Seluruh nilai ujian tetap utuh.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
