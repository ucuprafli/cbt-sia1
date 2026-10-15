<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            $table->dateTime('started_at');
            $table->dateTime('expires_at');          // batas waktu pribadi siswa ini
            $table->dateTime('finished_at')->nullable();

            $table->string('status')->default('in_progress'); // in_progress, finished, forfeited
            $table->unsignedTinyInteger('violation_count')->default(0);

            // hasil akhir (diisi saat selesai)
            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('wrong_count')->default(0);
            $table->unsignedInteger('empty_count')->default(0);
            $table->decimal('score', 5, 2)->nullable();
            $table->boolean('passed')->nullable();

            $table->timestamps();

            // satu siswa hanya boleh satu kali per ujian
            $table->unique(['exam_id', 'student_id']);
        });

        Schema::create('exam_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');                  // urutan soal, supaya stabil saat refresh
            $table->foreignId('answer_id')->nullable()->constrained()->nullOnDelete(); // pilihan siswa, null = kosong
            $table->boolean('is_correct')->nullable();            // diisi saat penilaian
            $table->timestamps();

            $table->unique(['exam_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_answers');
        Schema::dropIfExists('exam_attempts');
    }
};
