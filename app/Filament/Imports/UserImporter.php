<?php

namespace App\Filament\Imports;

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;

/**
 * Bulk student intake. Teachers and admins are created one at a time in the
 * panel; only students arrive by the hundred, so this importer always writes
 * role = murid and never touches an existing account's role.
 */
class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Nama lengkap')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:120']),

            ImportColumn::make('email')
                ->label('Email')
                ->requiredMapping()
                ->rules(['required', 'email', 'max:180']),

            ImportColumn::make('classroom')
                ->label('Kelas')
                ->requiredMapping()
                ->rules(['required', 'string'])
                // Resolved against existing classrooms by name. A typo fails the
                // row rather than quietly inventing a classroom nobody created.
                ->fillRecordUsing(function (User $record, string $state): void {
                    $classroom = Classroom::query()->where('name', trim($state))->first();

                    if ($classroom === null) {
                        throw new \RuntimeException("Kelas \"{$state}\" belum ada. Buat kelasnya lebih dulu.");
                    }

                    $record->classroom_id = $classroom->id;
                }),
        ];
    }

    public function resolveRecord(): ?User
    {
        // Re-running the same file updates rather than duplicates.
        $user = User::query()->firstOrNew(['email' => $this->data['email']]);

        if (! $user->exists) {
            $user->role = Role::Murid;
            $user->is_active = true;
            $user->password = Hash::make($this->options['default_password']);
        }

        return $user;
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            TextInput::make('default_password')
                ->label('Kata sandi awal')
                ->helperText('Dipakai untuk semua murid baru pada file ini. Sampaikan ke murid, lalu minta mereka menggantinya.')
                ->password()
                ->revealable()
                ->required()
                ->minLength(8),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Impor selesai. '.number_format($import->successful_rows).' murid berhasil diproses.';

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failed).' baris gagal dan bisa diunduh untuk diperbaiki.';
        }

        return $body;
    }
}
