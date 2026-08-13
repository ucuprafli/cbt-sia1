<?php

namespace App\Filament\Resources\MigrationRecords;

use App\Filament\Resources\MigrationRecords\Pages\ManageMigrationRecords;
use App\Models\MigrationRecord;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MigrationRecordResource extends Resource
{
    protected static ?string $model = MigrationRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $recordTitleAttribute = 'migration';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $modelLabel = 'Migration';

    protected static ?string $pluralModelLabel = 'Migrations';

    protected static ?string $navigationLabel = 'Data Migrations';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('migration')
                    ->label('Nama Migration')
                    ->placeholder('contoh: 2026_07_29_085443_create_subjects_table')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255),
                TextInput::make('batch')
                    ->label('Batch')
                    ->numeric()
                    ->required()
                    ->default(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('migration')
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('migration')
                    ->label('Nama Migration')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('batch')
                    ->label('Batch')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMigrationRecords::route('/'),
        ];
    }
}