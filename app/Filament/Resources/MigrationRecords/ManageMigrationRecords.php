<?php

namespace App\Filament\Resources\MigrationRecords\Pages;

use App\Filament\Resources\MigrationRecords\MigrationRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMigrationRecords extends ManageRecords
{
    protected static string $resource = MigrationRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}