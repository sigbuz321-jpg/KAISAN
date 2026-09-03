<?php

namespace App\Filament\Resources\Classrooms\Schemas;

use App\Enums\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ClassroomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama kelas')
                ->placeholder('Contoh: Kelas 9A')
                ->required()
                ->maxLength(120),

            Select::make('grade')
                ->label('Tingkat')
                ->options(collect(range(1, 12))->mapWithKeys(fn (int $g) => [$g => (string) $g])->all())
                ->required()
                ->native(false),

            TextInput::make('academic_year')
                ->label('Tahun ajaran')
                ->placeholder('2025/2026')
                ->required()
                ->maxLength(9)
                ->rule('regex:/^\d{4}\/\d{4}$/')
                ->validationMessages([
                    'regex' => 'Tahun ajaran ditulis seperti 2025/2026.',
                ]),

            // Who may open this class's exam results. Without it, teachers
            // could only see exams they wrote themselves.
            Select::make('teachers')
                ->label('Guru pengampu')
                ->multiple()
                ->relationship('teachers', 'name', fn (Builder $query) => $query->where('role', Role::Guru))
                ->preload()
                ->searchable()
                ->native(false)
                ->columnSpanFull()
                ->helperText('Guru yang diampukan ke kelas ini bisa melihat nilai ujian kelasnya, termasuk ujian yang dibuat guru lain.'),
        ]);
    }
}
