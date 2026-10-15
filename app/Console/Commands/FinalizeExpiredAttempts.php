<?php

namespace App\Console\Commands;

use App\Models\ExamAttempt;
use Illuminate\Console\Command;

class FinalizeExpiredAttempts extends Command
{
    protected $signature = 'exam:finalize-expired';

    protected $description = 'Hitung nilai attempt ujian yang waktunya sudah habis tapi belum dikirim siswa';

    public function handle(): int
    {
        $count = 0;

        ExamAttempt::expired()->with('exam')->get()->each(function (ExamAttempt $attempt) use (&$count) {
            $attempt->finalize('finished', $attempt->expires_at);
            $count++;
        });

        $this->info("{$count} attempt diselesaikan.");

        return self::SUCCESS;
    }
}
