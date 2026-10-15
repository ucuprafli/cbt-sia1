<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ExamAttempt extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'finished_at' => 'datetime',
        'score' => 'decimal:2',
        'passed' => 'boolean',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAttemptAnswer::class)->orderBy('position');
    }

    /** Attempt yang masih "in_progress" padahal waktunya sudah habis (murid pergi tanpa kirim jawaban). */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'in_progress')->where('expires_at', '<=', now());
    }

    /** Selesaikan semua attempt milik satu siswa yang waktunya sudah habis. */
    public static function finalizeExpiredFor(int $studentId): void
    {
        static::expired()
            ->where('student_id', $studentId)
            ->with('exam')
            ->get()
            ->each(fn (self $attempt) => $attempt->finalize('finished', $attempt->expires_at));
    }

    /**
     * Hitung nilai dan tutup attempt. Aman dipanggil berulang (diabaikan kalau sudah selesai).
     */
    public function finalize(string $status = 'finished', ?Carbon $finishedAt = null): void
    {
        if ($this->status !== 'in_progress') {
            return;
        }

        DB::transaction(function () use ($status, $finishedAt) {
            $rows = $this->answers()->with('question', 'answer')->get();

            $correct = 0;
            $wrong = 0;
            $empty = 0;
            $earned = 0;
            $total = 0;

            foreach ($rows as $row) {
                $weight = $row->question->score ?? 1;
                $total += $weight;

                if (! $row->answer_id) {
                    $empty++;
                    $row->update(['is_correct' => false]);

                    continue;
                }

                $isCorrect = (bool) ($row->answer->is_correct ?? false);
                $row->update(['is_correct' => $isCorrect]);

                if ($isCorrect) {
                    $correct++;
                    $earned += $weight;
                } else {
                    $wrong++;
                }
            }

            $score = $total > 0 ? round(($earned / $total) * 100, 2) : 0;

            $this->update([
                'status' => $status,
                'finished_at' => $finishedAt ?? now(),
                'correct_count' => $correct,
                'wrong_count' => $wrong,
                'empty_count' => $empty,
                'score' => $score,
                'passed' => $score >= $this->exam->threshold,
            ]);
        });
    }
}