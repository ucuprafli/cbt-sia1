<?php

namespace App\Filament\Resources\ExamAttempts\Pages;

use App\Filament\Resources\ExamAttempts\ExamAttemptResource;
use Filament\Resources\Pages\ListRecords;

class ListExamAttempts extends ListRecords
{
    protected static string $resource = ExamAttemptResource::class;
}