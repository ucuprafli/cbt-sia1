<?php

namespace App\Filament\Test\Resources\Exams\Pages;

use App\Filament\Test\Resources\Exams\ExamResource;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StartingExam extends Page
{
    protected static string $resource = ExamResource::class;

    protected string $view = 'filament.test.resources.exams.pages.starting-exam';

    protected static ?string $title = 'Pengerjaan Ujian';

    public Exam $exam;

    public ExamAttempt $attempt;

    public array $questions = [];

    /** Halaman aktif (mulai dari 1) */
    public int $page = 1;

    /** Jumlah soal per halaman */
    public int $perPage = 10;

    public int $maxViolations = 2;

    public bool $finished = false;

    public function mount(Exam $exam): void
    {
        $this->exam = $exam;
        $student = Auth::user()->student;

        abort_unless($student, 403, 'Akun ini tidak terdaftar sebagai siswa.');

        $this->attempt = DB::transaction(function () use ($exam, $student) {
            $attempt = ExamAttempt::query()
                ->where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->first();

            if ($attempt) {
                return $attempt;
            }

            $now = now();

            // batas waktu pribadi siswa ini tidak boleh melewati batas akhir ujian
            $hardDeadline = $exam->expired_at
                ?? $exam->started_at->copy()->addMinutes($exam->duration);

            $personalDeadline = $now->copy()->addMinutes($exam->duration);
            $expiresAt = $personalDeadline->greaterThan($hardDeadline) ? $hardDeadline : $personalDeadline;

            $attempt = ExamAttempt::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'started_at' => $now,
                'expires_at' => $expiresAt,
                'status' => 'in_progress',
            ]);

            // snapshot soal: diacak per mata pelajaran sesuai "Jumlah soal" yang diset guru
            $position = 1;
            foreach ($exam->subjects as $subject) {
                $questions = $subject->questions()
                    ->where('is_active', true)
                    ->inRandomOrder()
                    ->limit($subject->pivot->qty)
                    ->get();

                foreach ($questions as $question) {
                    ExamAttemptAnswer::create([
                        'exam_attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'position' => $position++,
                    ]);
                }
            }

            $attempt->update(['total_questions' => $position - 1]);

            return $attempt;
        });

        if ($this->attempt->status !== 'in_progress') {
            $this->redirectToResult();

            return;
        }

        if (now()->greaterThanOrEqualTo($this->attempt->expires_at)) {
            $this->finishAttempt('finished');
            $this->redirectToResult();

            return;
        }

        $this->loadQuestions();
    }

    protected function loadQuestions(): void
    {
        $this->questions = $this->attempt->answers()
            ->with([
                'question.subject',
                'question.answers' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('position')
            ->get()
            ->map(fn ($row) => [
                'attempt_answer_id' => $row->id,
                'position' => $row->position,
                'subject' => $row->question->subject->name,
                'payload' => $row->question->payload,
                'selected_answer_id' => $row->answer_id,
                'options' => $row->question->answers->map(fn ($a) => [
                    'id' => $a->id,
                    'text' => $a->text,
                ])->values()->toArray(),
            ])
            ->toArray();
    }

    public function getRemainingSecondsProperty(): int
    {
        if (now()->greaterThanOrEqualTo($this->attempt->expires_at)) {
            return 0;
        }

        return now()->diffInSeconds($this->attempt->expires_at, true);
    }

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil(count($this->questions) / $this->perPage));
    }

    /**
     * Soal untuk halaman aktif. Key array = index global soal (0-based),
     * supaya nomor soal tetap berlanjut (halaman 2 mulai dari 11, dst).
     */
    public function getPageQuestionsProperty(): array
    {
        return array_slice(
            $this->questions,
            ($this->page - 1) * $this->perPage,
            $this->perPage,
            true
        );
    }

    public function selectAnswer(int $attemptAnswerId, int $answerId): void
    {
        if ($this->finished) {
            return;
        }

        $key = collect($this->questions)
            ->search(fn ($q) => $q['attempt_answer_id'] === $attemptAnswerId);

        if ($key === false) {
            return;
        }

        // pastikan opsi memang milik soal ini
        if (! collect($this->questions[$key]['options'])->contains('id', $answerId)) {
            return;
        }

        ExamAttemptAnswer::where('id', $attemptAnswerId)
            ->where('exam_attempt_id', $this->attempt->id)
            ->update(['answer_id' => $answerId]);

        $this->questions[$key]['selected_answer_id'] = $answerId;
    }

    /** Loncat ke soal tertentu (dari kotak navigasi), pindah halaman kalau perlu. */
    public function goTo(int $index): void
    {
        if ($index < 0 || $index >= count($this->questions)) {
            return;
        }

        $this->page = intdiv($index, $this->perPage) + 1;
        $this->dispatch('scroll-to-question', index: $index);
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, min($page, $this->totalPages));
        $this->dispatch('scroll-to-question', index: ($this->page - 1) * $this->perPage);
    }

    public function nextPage(): void
    {
        $this->goToPage($this->page + 1);
    }

    public function previousPage(): void
    {
        $this->goToPage($this->page - 1);
    }

    public function recordViolation(): void
    {
        if ($this->finished) {
            return;
        }

        $this->attempt->increment('violation_count');
        $this->attempt->refresh();

        if ($this->attempt->violation_count >= $this->maxViolations) {
            $this->finishAttempt('forfeited');
            $this->redirectToResult();

            return;
        }

        $this->dispatch(
            'violation-warning',
            count: $this->attempt->violation_count,
            remaining: $this->maxViolations - $this->attempt->violation_count,
        );
    }

    public function timeUp(): void
    {
        if (! $this->finished) {
            $this->finishAttempt('finished');
        }

        $this->redirectToResult();
    }

    public function submit(): void
    {
        $this->finishAttempt('finished');
        $this->redirectToResult();
    }

    protected function finishAttempt(string $status): void
    {
        // logika penilaian ada di ExamAttempt::finalize() (dipakai juga oleh halaman Hasil Ujian & command)
        $this->attempt->finalize($status);

        $this->finished = true;
    }

    public function redirectToResult(): void
    {
        // matikan dialog "Leave site?" browser sebelum pindah ke halaman hasil
        $this->dispatch('exam-finished');
        $this->redirect(ExamResult::getUrl(['exam' => $this->exam->id]));
    }
}