<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

class Answer extends Model
{
    // trait
    // use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_correct'=> 'boolean',
    ];

    // relasi inverse ke model Question
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}