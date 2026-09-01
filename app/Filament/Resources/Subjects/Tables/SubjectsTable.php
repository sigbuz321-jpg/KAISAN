<?php

namespace App\Filament\Resources\Subjects\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('topics_count')->label('Jumlah bab')->counts('topics'),
                TextColumn::make('questions_count')->label('Jumlah soal')->counts('questions'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()->label('Ubah'),
                // SubjectPolicy blocks this while any question still hangs off it.
                DeleteAction::make()->label('Hapus'),
            ])
            ->emptyStateHeading('Belum ada mata pelajaran')
            ->emptyStateDescription('Buat mata pelajaran lebih dulu sebelum menulis soal.');
    }
}
