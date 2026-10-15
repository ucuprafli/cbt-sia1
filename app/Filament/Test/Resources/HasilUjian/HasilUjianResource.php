<?php

namespace App\Filament\Test\Resources\HasilUjian;

use App\ExamTypeEnum;
use App\Filament\Test\Resources\Exams\Pages\ExamResult;
use App\Filament\Test\Resources\HasilUjian\Pages\ListHasilUjian;
use App\Models\ExamAttempt;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Menu "Hasil Ujian" untuk siswa: riwayat nilai semua ujian yang pernah dikerjakan,
 * tetap bisa dibuka kapan saja walaupun sesi ujiannya sudah lewat dari daftar "Sesi Ujian".
 */
class HasilUjianResource extends Resource
{
    protected static ?string $model = ExamAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Hasil Ujian';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Hasil Ujian';

    protected static ?string $pluralModelLabel = 'Hasil Ujian';

    protected static ?string $slug = 'hasil-ujian';

    /** Siswa hanya boleh melihat hasil miliknya sendiri. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('student_id', Auth::user()?->student?->id ?? 0)
            ->where('status', '!=', 'in_progress')
            ->with('exam');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('exam.title')
                    ->label('Ujian')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('exam.type')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('finished_at')
                    ->label('Selesai')
                    ->dateTime('d F Y, H:i')
                    ->sortable(),
                TextColumn::make('score')
                    ->label('Nilai')
                    ->numeric(2)
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('correct_count')->label('Benar')->badge()->color('success'),
                TextColumn::make('wrong_count')->label('Salah')->badge()->color('danger'),
                TextColumn::make('empty_count')->label('Kosong')->badge()->color('gray'),
                TextColumn::make('hasil_status')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => $record->status === 'forfeited'
                        ? 'Dihentikan'
                        : ($record->passed ? 'Lulus' : 'Belum Lulus'))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Lulus' => 'success',
                        'Dihentikan' => 'warning',
                        default => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis Ujian')
                    ->placeholder('Semua jenis ujian')
                    ->options(ExamTypeEnum::class)
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('exam', fn (Builder $q) => $q->where('type', $data['value']))
                        : $query),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->recordActions([
                Action::make('lihat')
                    ->label('Lihat hasil')
                    ->icon(Heroicon::OutlinedEye)
                    ->button()
                    ->url(fn ($record) => ExamResult::getUrl(['exam' => $record->exam_id])),
            ])
            ->defaultSort('finished_at', 'desc')
            ->emptyStateHeading('Belum ada hasil ujian')
            ->emptyStateDescription('Hasil akan muncul di sini setelah kamu menyelesaikan ujian.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHasilUjian::route('/'),
        ];
    }
}
