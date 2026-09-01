<?php

namespace App\Filament\Resources\Classrooms\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClassroomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama kelas')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('grade')
                    ->label('Tingkat')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('academic_year')
                    ->label('Tahun ajaran')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('students_count')
                    ->label('Jumlah murid')
                    ->counts('students')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()->label('Ubah'),
                // ClassroomPolicy refuses to delete a classroom that still
                // holds students, so this only ever appears for empty ones.
                DeleteAction::make()->label('Hapus'),
            ])
            ->emptyStateHeading('Belum ada kelas')
            ->emptyStateDescription('Tambahkan kelas lebih dulu sebelum mengimpor murid.');
    }
}
