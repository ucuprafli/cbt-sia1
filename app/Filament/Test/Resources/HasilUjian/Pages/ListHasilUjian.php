<?php

namespace App\Filament\Test\Resources\HasilUjian\Pages;

use App\Filament\Test\Resources\HasilUjian\HasilUjianResource;
use App\Models\ExamAttempt;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListHasilUjian extends ListRecords
{
    protected static string $resource = HasilUjianResource::class;

    protected static ?string $title = 'Hasil Ujian';

    public function mount(): void
    {
        // Murid yang pergi tanpa menekan "Kirim Jawaban": nilainya dihitung otomatis
        // begitu waktunya habis, supaya hasilnya tidak "hilang".
        $studentId = Auth::user()?->student?->id;

        if ($studentId) {
            ExamAttempt::finalizeExpiredFor($studentId);
        }

        parent::mount();
    }
}
