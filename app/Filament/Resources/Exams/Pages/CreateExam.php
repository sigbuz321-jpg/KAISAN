<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Filament\Resources\Exams\ExamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExam extends CreateRecord
{
    protected static string $resource = ExamResource::class;

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        // Filled in properly once questions are attached. A teacher picks the
        // questions on the next screen, so anything entered here would be a
        // guess that the paper then contradicts.
        $data['question_count'] = 0;

        return $data;
    }

    /** Straight to the edit screen, where the questions are chosen. */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
