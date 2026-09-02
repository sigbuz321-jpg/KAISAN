<?php

namespace App\Filament\Resources\Questions\Tables;

use App\Actions\ChangeQuestionStatus;
use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Exceptions\QuestionWorkflowException;
use App\Models\Question;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stem')
                    ->label('Soal')
                    ->limit(70)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('subject.name')->label('Mapel')->sortable(),
                TextColumn::make('topic.name')->label('Bab')->placeholder('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (QuestionStatus $state) => $state->label())
                    ->color(fn (QuestionStatus $state) => $state->color()),

                TextColumn::make('source')
                    ->label('Asal')
                    ->badge()
                    ->formatStateUsing(fn (QuestionSource $state) => $state->label())
                    ->toggleable(),

                TextColumn::make('difficulty')->label('Kesulitan')->sortable()->toggleable(),
                TextColumn::make('author.name')->label('Dibuat oleh')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(QuestionStatus::options()),
                SelectFilter::make('source')->label('Asal')->options(QuestionSource::options()),
                SelectFilter::make('subject')->label('Mata pelajaran')->relationship('subject', 'name'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                // The exit criterion for M2: see the question exactly as a
                // student will. Renders the same component the exam screen uses.
                Action::make('pratinjau')
                    ->label('Pratinjau murid')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Tampilan di layar murid')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (Question $record) => view('components.question-preview', [
                        'question' => $record,
                        'showAnswer' => true,
                    ])),

                EditAction::make()->label('Ubah'),

                Action::make('ubahStatus')
                    ->label('Ubah status')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (Question $record) => auth()->user()?->can('changeStatus', $record) ?? false)
                    ->schema(fn (Question $record) => [
                        Select::make('status')
                            ->label('Pindahkan ke')
                            // Only transitions the workflow actually permits are
                            // offered, so the UI cannot suggest an invalid move.
                            ->options(collect($record->status->allowedNext())
                                ->mapWithKeys(fn (QuestionStatus $s) => [$s->value => $s->label()])
                                ->all())
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (Question $record, array $data, ChangeQuestionStatus $changeStatus) {
                        try {
                            $changeStatus->handle($record, QuestionStatus::from($data['status']), auth()->user());

                            Notification::make()
                                ->title('Status soal diperbarui.')
                                ->success()
                                ->send();
                        } catch (QuestionWorkflowException $e) {
                            Notification::make()
                                ->title('Status tidak bisa diubah')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // QuestionPolicy allows this only while the question is a draft.
                DeleteAction::make()->label('Hapus'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::bulkTransition(
                        name: 'setujuiMassal',
                        label: 'Setujui & terbitkan',
                        target: QuestionStatus::Published,
                        icon: 'heroicon-o-check-circle',
                        color: 'success',
                        heading: 'Terbitkan soal terpilih?',
                        description: 'Soal yang disetujui langsung bisa dipakai di ujian dan latihan. Pastikan Anda sudah membacanya.',
                    ),

                    self::bulkTransition(
                        name: 'tolakMassal',
                        label: 'Kembalikan ke draf',
                        target: QuestionStatus::Draft,
                        icon: 'heroicon-o-arrow-uturn-left',
                        color: 'warning',
                        heading: 'Kembalikan soal terpilih ke draf?',
                        description: 'Soal tidak dihapus. Anda bisa memperbaikinya lalu mengajukannya lagi.',
                    ),
                ])->label('Tindakan massal'),
            ])
            ->emptyStateHeading('Belum ada soal')
            ->emptyStateDescription('Tambah soal satu per satu, atau impor dari CSV.');
    }

    /**
     * Bulk approval exists because a teacher reviewing twenty AI drafts should
     * not have to click twenty times -- rule 1 of domain-kaisan.md asks for a
     * teacher's judgement, not for tedium.
     *
     * Each record still goes through ChangeQuestionStatus one at a time rather
     * than a single bulk UPDATE: the workflow guard, the approver stamp and the
     * AI-must-be-reviewed rule are per-record decisions, and a bulk update
     * would skip all three.
     */
    private static function bulkTransition(
        string $name,
        string $label,
        QuestionStatus $target,
        string $icon,
        string $color,
        string $heading,
        string $description,
    ): BulkAction {
        return BulkAction::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->modalHeading($heading)
            ->modalDescription($description)
            ->action(function (Collection $records, ChangeQuestionStatus $changeStatus) use ($target) {
                $actor = auth()->user();
                $changed = 0;
                $skipped = 0;

                foreach ($records as $record) {
                    if (! $actor->can('changeStatus', $record)) {
                        $skipped++;

                        continue;
                    }

                    try {
                        $changeStatus->handle($record, $target, $actor);
                        $changed++;
                    } catch (QuestionWorkflowException) {
                        // Wrong starting status for this move. Reported as a
                        // count rather than a wall of identical warnings.
                        $skipped++;
                    }
                }

                Notification::make()
                    ->title("{$changed} soal diperbarui.")
                    ->body($skipped > 0 ? "{$skipped} soal dilewati karena statusnya tidak memungkinkan." : null)
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
