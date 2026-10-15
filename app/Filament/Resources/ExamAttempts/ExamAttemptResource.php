<?php

namespace App\Filament\Resources\ExamAttempts;

use App\ExamTypeEnum;
use App\Filament\Resources\ExamAttempts\Pages\ListExamAttempts;
use App\Filament\Resources\ExamAttempts\Pages\ViewExamAttempt;
use App\Models\ExamAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ExamAttemptResource extends Resource
{
    protected static ?string $model = ExamAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Rekap & Histori';

    protected static ?string $navigationLabel = 'Rekap Ujian';

    protected static ?string $modelLabel = 'Rekap Ujian';

    protected static ?string $pluralModelLabel = 'Rekap Ujian';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('status', '!=', 'in_progress'))
            ->columns([
                TextColumn::make('student.name')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('exam.title')
                    ->label('Ujian')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('exam.type')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('subjects')
                    ->label('Mata Pelajaran')
                    ->getStateUsing(fn ($record) => $record->exam->subjects->pluck('name')->join(', ')),
                TextColumn::make('finished_at')
                    ->label('Tanggal Ujian')
                    ->dateTime('d F Y, H:i')
                    ->sortable(),
                TextColumn::make('score')
                    ->label('Nilai')
                    ->sortable(),
                TextColumn::make('correct_count')->label('Benar')->badge()->color('success'),
                TextColumn::make('wrong_count')->label('Salah')->badge()->color('danger'),
                TextColumn::make('empty_count')->label('Kosong')->badge()->color('gray'),
                IconColumn::make('passed')->label('Lulus')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis Ujian')
                    ->options(ExamTypeEnum::class)
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('exam', fn (Builder $q) => $q->where('type', $data['value']))
                        : $query),
                SelectFilter::make('exam_id')->label('Ujian')->relationship('exam', 'title'),
                SelectFilter::make('passed')->label('Status')->options([1 => 'Lulus', 0 => 'Tidak Lulus']),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->defaultSort('finished_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExamAttempts::route('/'),
            'view' => ViewExamAttempt::route('/{record}'),
        ];
    }
}