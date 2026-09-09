<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use App\Enums\SchoolLevel;
use App\Models\Classroom;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama lengkap')
                ->required()
                ->maxLength(120),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(180),

            Select::make('role')
                ->label('Peran')
                ->options(fn () => auth()->user()?->isAdmin() ? Role::options() : [Role::Murid->value => Role::Murid->label()])
                ->default(Role::Murid->value)
                ->required()
                ->native(false)
                ->disabled(fn () => ! auth()->user()?->isAdmin())
                ->dehydrated()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    // Moving away from murid must clear student fields, or
                    // database constraints reject the row.
                    if ($state !== Role::Murid->value) {
                        $set('school_level', null);
                        $set('grade', null);
                        $set('classroom_id', null);
                    }
                }),

            Select::make('school_level')
                ->label('Jenjang sekolah')
                ->placeholder('Pilih jenjang sekolah...')
                ->options(SchoolLevel::options())
                ->native(false)
                ->visible(fn (callable $get) => $get('role') === Role::Murid->value)
                ->live()
                ->afterStateUpdated(function (?string $state, callable $set, callable $get) {
                    if ($state !== null && $state !== '') {
                        $level = SchoolLevel::tryFrom($state);
                        if ($level !== null) {
                            $currentGrade = $get('grade');
                            if (! $currentGrade || ! in_array((int) $currentGrade, $level->grades(), true)) {
                                $set('grade', $level->defaultStartGrade());
                            }
                        }
                    }
                })
                ->helperText('SD (Kelas 1–6), SMP (Kelas 7–9), SMA (Kelas 10–12).'),

            Select::make('grade')
                ->label('Kelas')
                ->placeholder('Pilih kelas...')
                ->options(function (callable $get) {
                    $levelState = $get('school_level');
                    $level = $levelState ? SchoolLevel::tryFrom($levelState) : null;
                    if ($level !== null) {
                        return $level->gradeOptions();
                    }

                    return collect(range(1, 12))->mapWithKeys(fn (int $g) => [$g => "Kelas {$g}"])->all();
                })
                ->native(false)
                ->visible(fn (callable $get) => $get('role') === Role::Murid->value)
                ->live()
                ->afterStateUpdated(function (?int $state, callable $set) {
                    if ($state !== null) {
                        $level = SchoolLevel::fromGrade((int) $state);
                        if ($level !== null) {
                            $set('school_level', $level->value);
                        }
                    }
                }),

            Select::make('classroom_id')
                ->label('Kelas Rombel')
                ->placeholder('Pilih rombel...')
                ->options(function (callable $get) {
                    $grade = $get('grade');
                    $levelState = $get('school_level');
                    $level = $levelState ? SchoolLevel::tryFrom($levelState) : null;

                    return Classroom::query()
                        ->when($grade, fn ($q) => $q->where('grade', (int) $grade))
                        ->when(! $grade && $level, fn ($q) => $q->whereBetween('grade', [$level->defaultStartGrade(), $level->defaultEndGrade()]))
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (Classroom $c) => [$c->id => $c->displayName()])
                        ->all();
                })
                ->searchable()
                ->native(false)
                ->visible(fn (callable $get) => $get('role') === Role::Murid->value)
                ->helperText('Rombongan belajar untuk ujian terjadwal.')
                ->live()
                ->afterStateUpdated(function (?int $state, callable $set) {
                    if ($state !== null) {
                        $classroom = Classroom::find($state);
                        if ($classroom !== null) {
                            $set('grade', $classroom->grade);
                            $level = SchoolLevel::fromGrade($classroom->grade);
                            if ($level !== null) {
                                $set('school_level', $level->value);
                            }
                        }
                    }
                }),

            TextInput::make('password')
                ->label('Kata sandi')
                ->password()
                ->revealable()
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn (?string $state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->minLength(8)
                ->helperText('Kosongkan kalau tidak ingin mengubah kata sandi.'),

            Toggle::make('is_active')
                ->label('Akun aktif')
                ->default(true)
                ->helperText('Menonaktifkan akun tidak menghapus riwayat nilainya.'),
        ]);
    }
}
