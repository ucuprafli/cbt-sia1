<?php

namespace App\Filament\Pages;

use App\Models\ExamAttemptAnswer;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SubjectHistory extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Rekap & Histori';

    protected static ?string $navigationLabel = 'Histori Pelajaran';

    protected static ?string $title = 'Histori Pelajaran';

    protected string $view = 'filament.pages.subject-history';

    public array $rows = [];

    public function mount(): void
    {
        $this->rows = ExamAttemptAnswer::query()
            ->whereHas('attempt', fn ($q) => $q->where('status', '!=', 'in_progress'))
            ->with(['attempt.student', 'question.subject'])
            ->get()
            ->filter(fn ($row) => $row->attempt?->student && $row->question?->subject)
            ->groupBy(fn ($row) => $row->attempt->student_id.'-'.$row->question->subject_id)
            ->map(function ($rows) {
                $student = $rows->first()->attempt->student;
                $subject = $rows->first()->question->subject;

                $byAttempt = $rows->groupBy('exam_attempt_id');

                $scores = $byAttempt->map(function ($attemptRows) {
                    $totalWeight = $attemptRows->sum(fn ($r) => $r->question->score ?? 1);
                    $earned = $attemptRows->where('is_correct', true)->sum(fn ($r) => $r->question->score ?? 1);

                    return $totalWeight > 0 ? round(($earned / $totalWeight) * 100, 2) : 0;
                });

                $latestAttemptId = $byAttempt->keys()->sortByDesc(
                    fn ($id) => optional($byAttempt[$id]->first()->attempt->finished_at)?->timestamp
                )->first();

                return [
                    'siswa' => $student->name,
                    'nis' => $student->nis,
                    'pelajaran' => $subject->name,
                    'jumlah_ujian' => $scores->count(),
                    'rata_rata' => round($scores->avg(), 2),
                    'tertinggi' => $scores->max(),
                    'terakhir' => $scores->get($latestAttemptId),
                ];
            })
            ->sortBy([['siswa', 'asc'], ['pelajaran', 'asc']])
            ->values()
            ->toArray();
    }
}