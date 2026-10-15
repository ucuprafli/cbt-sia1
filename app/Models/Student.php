<?php

namespace App\Models;

use App\StudentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class Student extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    #[Override]
    protected function casts()
    {
        return [
            'gender' => 'boolean',
            'status' => StudentStatusEnum::class,
        ];
    }

    // relasi antar tabel
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function examAttempts(): HasMany
{
    return $this->hasMany(ExamAttempt::class);
}

}