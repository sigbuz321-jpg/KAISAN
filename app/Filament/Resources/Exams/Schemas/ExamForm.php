<?php

namespace App\Filament\Resources\Exams\Schemas;

use App\Models\Season;
use App\Models\Subject;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ujian')
                ->schema([
                    TextInput::make('title')
                        ->label('Judul ujian')
                        ->placeholder('Contoh: Ulangan Harian Aljabar')
                        ->required()
                        ->maxLength(180)
                        ->columnSpanFull(),

                    Select::make('subject_id')
                        ->label('Mata pelajaran')
                        ->options(fn () => Subject::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->required()
                        ->searchable()
                        ->native(false)
                        // Questions belong to a subject, so changing it after
                        // they are picked would leave a mismatched paper.
                        ->disabled(fn (?object $record) => $record !== null)
                        ->helperText(fn (?object $record) => $record !== null
                            ? 'Mata pelajaran tidak bisa diubah setelah ujian dibuat.'
                            : null),

                    Select::make('season_id')
                        ->label('Musim')
                        ->options(fn () => Season::query()->orderByDesc('starts_at')->pluck('name', 'id')->all())
                        ->default(fn () => Season::current()?->id)
                        ->required()
                        ->native(false)
                        ->helperText('Peringkat dihitung per musim.'),
                ])
                ->columns(2),

            Section::make('Jadwal')
                ->schema([
                    DateTimePicker::make('starts_at')
                        ->label('Mulai')
                        ->seconds(false)
                        ->required()
                        ->native(false),

                    DateTimePicker::make('ends_at')
                        ->label('Ditutup')
                        ->seconds(false)
                        ->required()
                        ->native(false)
                        ->after('starts_at')
                        ->helperText('Setelah jam ini tidak ada lagi yang bisa mengumpulkan.'),

                    TextInput::make('duration_minutes')
                        ->label('Durasi pengerjaan (menit)')
                        ->numeric()
                        ->default(60)
                        ->minValue(1)
                        ->maxValue(480)
                        ->required()
                        ->helperText('Waktu tiap murid dihitung sejak ia menekan Mulai. Murid yang mulai terlambat tetap berhenti saat ujian ditutup.'),

                    TextInput::make('difficulty_weight')
                        ->label('Bobot kesulitan')
                        ->numeric()
                        ->default('1.00')
                        ->minValue(1)
                        ->maxValue(2)
                        ->step(0.05)
                        ->required()
                        ->helperText('1,00 sampai 2,00. Ujian yang lebih sulit memberi poin peringkat lebih besar.'),
                ])
                ->columns(2),

            Section::make('Pengacakan')
                ->schema([
                    Toggle::make('shuffle_questions')
                        ->label('Acak urutan soal')
                        ->default(true)
                        ->helperText('Tiap murid mendapat urutan berbeda.'),

                    Toggle::make('shuffle_options')
                        ->label('Acak urutan pilihan jawaban')
                        ->default(true)
                        ->helperText('Penilaian tetap benar; urutan hanya berbeda di layar.'),
                ])
                ->columns(2),
        ]);
    }
}
