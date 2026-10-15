<?php

namespace App\Filament\Test\Pages;

use App\ExamTypeEnum;
use App\Filament\Test\Resources\HasilUjian\HasilUjianResource;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

/**
 * Dashboard murid: ringkasan "Nilai Ujian" per jenis ujian (Quiz/UTS/UAS/Kelulusan).
 * Detail lengkap ada di menu "Hasil Ujian".
 */
class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.test.pages.dashboard';

    protected static ?string $title = 'Dashboard';

    /** Jenis ujian yang sedang dipilih (value enum: quiz, uts, uas, ...). */
    public ?string $type = null;

    public function getWidgets(): array
    {
        return [];
    }

    public function mount(): void
    {
        $student = Auth::user()?->student;

        if ($student) {
            // murid yang pergi tanpa kirim jawaban: nilainya dihitung otomatis kalau waktunya sudah habis
            ExamAttempt::finalizeExpiredFor($student->id);
        }

        $this->type = $this->types[0]['value'] ?? null;
    }

    public function selectType(string $type): void
    {
        if (ExamTypeEnum::tryFrom($type)) {
            $this->type = $type;
        }
    }

    public function getStudentNameProperty(): string
    {
        return Auth::user()?->student?->name ?? Auth::user()?->name ?? '';
    }

    /** Jenis ujian yang sudah punya nilai untuk murid ini (urut sesuai enum). */
    public function getTypesProperty(): array
    {
        $student = Auth::user()?->student;

        if (! $student) {
            return [];
        }

        $found = ExamAttempt::query()
            ->where('student_id', $student->id)
            ->where('status', '!=', 'in_progress')
            ->with('exam')
            ->get()
            ->map(fn ($attempt) => $attempt->exam?->type?->value)
            ->filter()
            ->unique()
            ->all();

        return collect(ExamTypeEnum::cases())
            ->filter(fn (ExamTypeEnum $case) => in_array($case->value, $found, true))
            ->map(fn (ExamTypeEnum $case) => [
                'value' => $case->value,
                'label' => $case->getLabel(),
            ])
            ->values()
            ->all();
    }

    /** Ringkasan nilai untuk jenis ujian yang dipilih. */
    public function getSummaryProperty(): ?array
    {
        $student = Auth::user()?->student;

        if (! $student || ! $this->type) {
            return null;
        }

        $attempts = ExamAttempt::query()
            ->where('student_id', $student->id)
            ->where('status', '!=', 'in_progress')
            ->whereHas('exam', fn ($q) => $q->where('type', $this->type))
            ->with('exam')
            ->orderByDesc('finished_at')
            ->get();

        if ($attempts->isEmpty()) {
            return null;
        }

        $answers = ExamAttemptAnswer::query()
            ->whereIn('exam_attempt_id', $attempts->pluck('id'))
            ->with('question.subject')
            ->get()
            ->groupBy('exam_attempt_id');

        $subjects = [];

        foreach ($attempts as $attempt) {
            $bySubject = ($answers[$attempt->id] ?? collect())
                ->filter(fn ($row) => $row->question?->subject)
                ->groupBy(fn ($row) => $row->question->subject_id);

            foreach ($bySubject as $rows) {
                $total = $rows->sum(fn ($r) => $r->question->score ?? 1);
                $earned = $rows->where('is_correct', true)->sum(fn ($r) => $r->question->score ?? 1);
                $score = $total > 0 ? round(($earned / $total) * 100, 2) : 0;

                $status = $attempt->status === 'forfeited'
                    ? 'dihentikan'
                    : ($score >= (float) $attempt->exam->threshold ? 'lulus' : 'tidak_lulus');

                $subjects[] = [
                    'name' => $rows->first()->question->subject->name,
                    'exam' => $attempt->exam->title,
                    'score' => $score,
                    'status' => $status,
                ];
            }
        }

        $counts = collect($subjects)->countBy('status');
        $forfeited = $attempts->contains(fn ($a) => $a->status === 'forfeited');
        $passed = ! $forfeited && $attempts->every(fn ($a) => (bool) $a->passed);

        return [
            'overall' => round((float) $attempts->avg('score'), 2),
            'state' => $forfeited ? 'dihentikan' : ($passed ? 'lulus' : 'tidak_lulus'),
            'exams' => $attempts->map(fn ($a) => $a->exam->title)->unique()->values()->all(),
            'threshold' => (float) $attempts->first()->exam->threshold,
            'finished_at' => $attempts->first()->finished_at,
            'subjects' => $subjects,
            'lulus' => $counts->get('lulus', 0),
            'tidak_lulus' => $counts->get('tidak_lulus', 0),
            'dihentikan' => $counts->get('dihentikan', 0),
            'url' => HasilUjianResource::getUrl('index', [
                'tableFilters' => ['type' => ['value' => $this->type]],
            ]),
        ];
    }
}
