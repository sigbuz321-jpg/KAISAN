<?php

namespace App\Filament\Resources\Questions\Tables;

use App\Actions\BulkChangeQuestionStatus;
use App\Actions\ChangeQuestionStatus;
use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Exceptions\QuestionWorkflowException;
use App\Models\Question;
use App\Services\Adaptive\QuestionDifficulty;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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

                // Fed by adaptive practice. A question almost nobody gets right
                // is usually ambiguous or mis-keyed rather than hard, and only
                // a teacher can tell those apart.
                TextColumn::make('tingkat_benar')
                    ->label('Dijawab benar')
                    ->badge()
                    ->state(fn (Question $record) => $record->correctRate() === null
                        ? 'Belum dikerjakan'
                        : round($record->correctRate() * 100).'% dari '.$record->times_answered)
                    ->color(fn (Question $record) => app(QuestionDifficulty::class)
                        ->looksSuspect($record->times_answered, $record->times_correct) ? 'danger' : 'gray'),

                TextColumn::make('author.name')->label('Dibuat oleh')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(QuestionStatus::options()),
                SelectFilter::make('source')->label('Asal')->options(QuestionSource::options()),
                SelectFilter::make('subject')->label('Mata pelajaran')->relationship('subject', 'name'),

                Filter::make('bermasalah')
                    ->label('Perlu ditinjau')
                    ->toggle()
                    ->query(fn (Builder $query) => $query
                        ->where('times_answered', '>=', QuestionDifficulty::SUSPECT_AFTER_ANSWERS)
                        ->whereRaw('times_correct::float / times_answered < ?', [QuestionDifficulty::SUSPECT_CORRECT_RATE])),
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

    /** The counting and the workflow rules live in BulkChangeQuestionStatus. */
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
            ->action(function (Collection $records, BulkChangeQuestionStatus $bulk) use ($target) {
                $result = $bulk->handle($records, $target, auth()->user());

                Notification::make()
                    ->title("{$result['changed']} soal diperbarui.")
                    ->body($result['skipped'] > 0
                        ? "{$result['skipped']} soal dilewati karena statusnya tidak memungkinkan."
                        : null)
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
