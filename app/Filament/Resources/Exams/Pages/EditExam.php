<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Actions\ScheduleExam;
use App\Enums\ExamStatus;
use App\Exceptions\ExamWorkflowException;
use App\Filament\Resources\Exams\ExamResource;
use App\Models\Exam;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditExam extends EditRecord
{
    protected static string $resource = ExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('jadwalkan')
                ->label('Jadwalkan ujian')
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
        ];
    }
}
