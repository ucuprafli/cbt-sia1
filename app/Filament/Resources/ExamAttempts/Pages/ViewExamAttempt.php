<?php

namespace App\Filament\Resources\ExamAttempts\Pages;

use App\Filament\Resources\ExamAttempts\ExamAttemptResource;
use App\Models\ExamAttempt;
use Filament\Resources\Pages\Page;

class ViewExamAttempt extends Page
{
    protected static string $resource = ExamAttemptResource::class;

    protected string $view = 'filament.resources.exam-attempts.pages.view-exam-attempt';

    public ExamAttempt $record;

    public array $rows = [];

    public function mount(ExamAttempt $record): void
    {
        $this->record = $record->load('student', 'exam.subjects');

        $this->rows = $record->answers()
            ->with(['question.subject', 'question.answers', 'answer'])
            ->orderBy('position')
            ->get()
            ->map(fn ($row) => [
                'position' => $row->position,
                'subject' => $row->question->subject->name,
                'payload' => $row->question->payload,
                'selected_answer_id' => $row->answer_id,
                'is_correct' => $row->is_correct,
                'options' => $row->question->answers->map(fn ($a) => [
                    'id' => $a->id,
                    'text' => $a->text,
                    'is_correct' => $a->is_correct,
                ])->toArray(),
            ])->toArray();
    }
}