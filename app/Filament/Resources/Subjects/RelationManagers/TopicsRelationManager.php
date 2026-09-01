<?php

namespace App\Filament\Resources\Subjects\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TopicsRelationManager extends RelationManager
{
    protected static string $relationship = 'topics';

    protected static ?string $title = 'Bab';

    protected static ?string $modelLabel = 'Bab';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama bab')
                ->placeholder('Contoh: Aljabar')
                ->required()
                ->maxLength(160),

            TextInput::make('order')
                ->label('Urutan')
                ->numeric()
                ->default(0)
                ->helperText('Angka kecil tampil lebih dulu.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')->label('Urutan')->sortable(),
                TextColumn::make('name')->label('Nama bab')->searchable(),
                TextColumn::make('questions_count')->label('Jumlah soal')->counts('questions'),
            ])
            ->defaultSort('order')
            ->headerActions([
                CreateAction::make()->label('Tambah bab'),
            ])
            ->recordActions([
                EditAction::make()->label('Ubah'),
                DeleteAction::make()->label('Hapus'),
            ])
            ->emptyStateHeading('Belum ada bab');
    }
}
