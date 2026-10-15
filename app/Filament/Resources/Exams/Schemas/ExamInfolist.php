<?php

namespace App\Filament\Resources\Exams\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExamInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label('Nama Ujian'),
                TextEntry::make('type')
                    ->label('Jenis Ujian')
                    ->badge(),
                TextEntry::make('duration')
                    ->label('Durasi (menit)'),
                TextEntry::make('threshold')
                    ->label('Ambang Batas'),
                TextEntry::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('-'),
                TextEntry::make('expired_at')
                    ->label('Berakhir')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
