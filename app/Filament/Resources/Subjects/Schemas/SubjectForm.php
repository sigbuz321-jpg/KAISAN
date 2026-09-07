<?php

namespace App\Filament\Resources\Subjects\Schemas;

use App\Enums\SchoolLevel;
use Filament\Forms\Components\Select;
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

            Select::make('school_level')
                ->label('Jenjang')
                ->placeholder('Umum (semua jenjang)')
                ->options(SchoolLevel::options())
                ->native(false)
                ->live()
                ->afterStateUpdated(function (?string $state, callable $set) {
                    if ($state === null || $state === '') {
                        return;
                    }

                    $level = SchoolLevel::from($state);
                    $set('start_grade', $level->defaultStartGrade());
                    $set('end_grade', $level->defaultEndGrade());
                }),

            TextInput::make('start_grade')
                ->label('Kelas awal')
                ->numeric()
                ->minValue(1)
                ->maxValue(12)
                ->helperText('Pilih jenjang di atas untuk mengisi otomatis, atau ketik manual.'),

            TextInput::make('end_grade')
                ->label('Kelas akhir')
                ->numeric()
                ->minValue(1)
                ->maxValue(12)
                ->gte('start_grade')
                ->helperText('Harus lebih besar atau sama dengan kelas awal.'),

            TextInput::make('slug')
                ->label('Slug')
                ->helperText('Kosongkan saja, akan dibuat otomatis dari nama + jenjang.')
                ->unique(ignoreRecord: true)
                ->maxLength(140),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true)
                ->helperText('Mata pelajaran nonaktif tidak muncul saat membuat soal baru.'),
        ]);
    }
}
