<?php

namespace App\Filament\Resources\Questions\Tables;

use App\Actions\ChangeQuestionStatus;
use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Exceptions\QuestionWorkflowException;
use App\Models\Question;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
            ->emptyStateHeading('Belum ada soal')
            ->emptyStateDescription('Tambah soal satu per satu, atau impor dari CSV.');
    }
}
