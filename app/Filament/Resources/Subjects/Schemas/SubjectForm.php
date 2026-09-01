<?php

namespace App\Filament\Resources\Subjects\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama mata pelajaran')
                ->placeholder('Contoh: Matematika')
                ->required()
                ->maxLength(120),

            TextInput::make('slug')
                ->label('Slug')
                ->helperText('Kosongkan saja, akan dibuat otomatis dari nama.')
                ->unique(ignoreRecord: true)
                ->maxLength(140),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true)
                ->helperText('Mata pelajaran nonaktif tidak muncul saat membuat soal baru.'),
        ]);
    }
}
