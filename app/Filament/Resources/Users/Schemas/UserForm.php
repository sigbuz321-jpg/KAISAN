<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
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
                ->options(Role::options())
                ->required()
                ->native(false)
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    // Moving away from murid must clear the classroom, or the
                    // users_classroom_only_for_students CHECK rejects the row.
                    if ($state !== Role::Murid->value) {
                        $set('classroom_id', null);
                    }
                }),

            Select::make('classroom_id')
                ->label('Kelas')
                ->options(fn () => Classroom::query()
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(fn (Classroom $c) => [$c->id => $c->displayName()])
                    ->all())
                ->searchable()
                ->native(false)
                ->visible(fn (callable $get) => $get('role') === Role::Murid->value)
                ->helperText('Hanya murid yang punya kelas.'),

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
