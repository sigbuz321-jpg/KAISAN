<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Imports\UserImporter;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Tambah akun'),

            ImportAction::make()
                ->importer(UserImporter::class)
                ->label('Impor murid dari CSV')
                ->modalHeading('Impor murid dari CSV')
                ->modalDescription('File CSV dengan kolom nama, email, dan kelas. Kelas harus sudah dibuat lebih dulu.')
                ->color('gray'),
        ];
    }
}
