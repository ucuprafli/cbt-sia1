<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'score' => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->orderBy('id');
    }

    // jawaban yang benar (dipakai untuk penilaian ujian nanti)
    public function correctAnswer(): HasOne
    {
        return $this->hasOne(Answer::class)->where('is_correct', true);
    }

    // Daftar jawaban dengan label huruf A, B, C... berdasarkan urutan tampil.
    // Huruf TIDAK disimpan di database.
    public function getAnswersWithLetterAttribute()
    {
        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];

        return $this->answers->values()->map(fn ($answer, $index) => [
            'letter' => $letters[$index] ?? '-',
            'id' => $answer->id,
            'text' => $answer->text,
            'is_correct' => $answer->is_correct,
        ]);
    }
}