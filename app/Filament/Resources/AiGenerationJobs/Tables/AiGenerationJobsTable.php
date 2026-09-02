<?php

namespace App\Filament\Resources\AiGenerationJobs\Tables;

use App\Enums\AiJobStatus;
use App\Enums\DifficultyBand;
use App\Models\AiGenerationJob;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AiGenerationJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Diminta')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('subject.name')->label('Mapel')->sortable(),
                TextColumn::make('topic.name')->label('Bab')->placeholder('Semua bab'),

                TextColumn::make('difficulty')
                    ->label('Kesulitan')
                    ->formatStateUsing(fn (DifficultyBand $state) => $state->label()),

                TextColumn::make('count')->label('Diminta')->alignEnd(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (AiJobStatus $state) => $state->label())
                    ->color(fn (AiJobStatus $state) => $state->color()),

                // Reads straight from meta so a teacher can see, at a glance,
                // that asking for 20 does not always mean getting 20.
                TextColumn::make('hasil')
                    ->label('Tersimpan')
                    ->state(fn (AiGenerationJob $record) => $record->status === AiJobStatus::Done
                        ? $record->savedCount().' dari '.$record->count
                        : '-')
                    ->description(fn (AiGenerationJob $record) => self::discardNote($record)),

                TextColumn::make('requester.name')
                    ->label('Diminta oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('estimated_cost')
                    ->label('Perkiraan biaya')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(AiJobStatus::options()),
                SelectFilter::make('subject')->label('Mata pelajaran')->relationship('subject', 'name'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('10s')
            ->recordActions([
                Action::make('alasan')
                    ->label('Lihat rincian')
                    ->icon('heroicon-o-information-circle')
                    ->color('gray')
                    ->modalHeading('Rincian permintaan')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (AiGenerationJob $record) => view('filament.ai.generation-detail', [
                        'job' => $record,
                    ])),
            ])
            ->emptyStateHeading('Belum ada permintaan soal AI')
            ->emptyStateDescription('Gunakan tombol "Buat soal dengan AI" untuk meminta soal baru.');
    }

    private static function discardNote(AiGenerationJob $record): ?string
    {
        if ($record->status !== AiJobStatus::Done) {
            return null;
        }

        $discarded = $record->rejectedCount() + $record->duplicateCount();

        return $discarded > 0 ? "{$discarded} dilewati" : null;
    }
}
