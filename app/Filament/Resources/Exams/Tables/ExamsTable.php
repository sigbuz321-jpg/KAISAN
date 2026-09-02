<?php

namespace App\Filament\Resources\Exams\Tables;

use App\Actions\ScheduleExam;
use App\Enums\ExamStatus;
use App\Exceptions\ExamWorkflowException;
use App\Filament\Resources\Exams\Pages\ExamResults;
use App\Models\Exam;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->wrap(),
                TextColumn::make('subject.name')->label('Mapel')->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ExamStatus $state) => $state->label())
                    ->color(fn (ExamStatus $state) => $state->color()),

                TextColumn::make('starts_at')->label('Mulai')->dateTime('d M Y, H:i')->sortable(),
                TextColumn::make('ends_at')->label('Ditutup')->dateTime('d M Y, H:i')->toggleable(),

                TextColumn::make('questions_count')
                    ->label('Soal')
                    ->counts('questions')
                    ->alignEnd(),

                TextColumn::make('attempts_count')
                    ->label('Dikerjakan')
                    ->counts('attempts')
                    ->alignEnd(),

                TextColumn::make('duration_minutes')->label('Durasi')->suffix(' menit')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(ExamStatus::options()),
                SelectFilter::make('subject')->label('Mata pelajaran')->relationship('subject', 'name'),
            ])
            ->defaultSort('starts_at', 'desc')
            ->recordActions([
                Action::make('jadwalkan')
                    ->label('Jadwalkan')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Jadwalkan ujian ini?')
                    ->modalDescription('Setelah dijadwalkan, daftar soal tidak bisa diubah lagi dan ujian akan terlihat oleh murid. Bila perlu revisi, buat ujian baru.')
                    ->visible(fn (Exam $record) => $record->status === ExamStatus::Draft)
                    ->action(function (Exam $record, ScheduleExam $schedule) {
                        try {
                            $schedule->handle($record);

                            Notification::make()
                                ->title('Ujian dijadwalkan.')
                                ->body('Ujian akan terbuka sendiri pada waktu yang ditentukan.')
                                ->success()
                                ->send();
                        } catch (ExamWorkflowException $e) {
                            Notification::make()
                                ->title('Belum bisa dijadwalkan')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('nilai')
                    ->label('Lihat nilai')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('gray')
                    ->visible(fn (Exam $record) => auth()->user()?->can('viewResults', $record) ?? false)
                    ->url(fn (Exam $record) => ExamResults::getUrl(['record' => $record])),

                EditAction::make()->label('Ubah'),

                // ExamPolicy allows this only for a draft nobody has sat.
                DeleteAction::make()->label('Hapus'),
            ])
            ->emptyStateHeading('Belum ada ujian')
            ->emptyStateDescription('Buat ujian, pilih soalnya, lalu jadwalkan.');
    }
}
