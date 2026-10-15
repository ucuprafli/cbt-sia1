<?php

namespace App\Filament\Test\Resources\Exams\Pages;

use App\Filament\Test\Resources\Exams\ExamResource;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ExamResult extends Page
{
    protected static string $resource = ExamResource::class;

    protected string $view = 'filament.test.resources.exams.pages.exam-result';

    protected static ?string $title = 'Hasil Ujian';

    public Exam $exam;

    public ExamAttempt $attempt;

    public bool $canReview = false;

    public array $reviewRows = [];

    public function mount(Exam $exam): void
    {
        $this->exam = $exam;
        $student = Auth::user()->student;

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $student?->id)
            ->first();

        abort_unless($attempt && $attempt->status !== 'in_progress', 404);

        $this->attempt = $attempt;

        // kunci jawaban hanya tampil setelah waktu ujian berakhir untuk semua siswa,
        // supaya siswa yang selesai duluan tidak bisa membocorkan jawaban
        $deadline = $exam->expired_at ?? $exam->started_at->copy()->addMinutes($exam->duration);
        $this->canReview = now()->greaterThanOrEqualTo($deadline);

        if (! $this->canReview) {
            return;
        }

        $this->reviewRows = $attempt->answers()
            ->with([
                'question.answers' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('position')
            ->get()
            ->map(fn ($row) => [
                'position' => $row->position,
                'payload' => $row->question->payload,
                'selected_answer_id' => $row->answer_id,
                'is_correct' => $row->is_correct,
                'options' => $row->question->answers->map(fn ($a) => [
                    'id' => $a->id,
                    'text' => $a->text,
                    'is_correct' => $a->is_correct,
                ])->toArray(),
            ])
            ->toArray();
    }
}