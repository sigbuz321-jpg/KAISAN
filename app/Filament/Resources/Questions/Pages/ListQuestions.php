<?php

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Imports\QuestionImporter;
use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tulis soal'),

            ImportAction::make()
                ->importer(QuestionImporter::class)
                ->label('Impor soal dari CSV')
                ->modalHeading('Impor soal dari CSV')
                ->modalDescription('Kolom: subject, topic, stem, option_a, option_b, option_c, option_d, answer_key, explanation, difficulty. Semua soal masuk sebagai draf.')
                ->color('gray'),
        ];
    }
}
