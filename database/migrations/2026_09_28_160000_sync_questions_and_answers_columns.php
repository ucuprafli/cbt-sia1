<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // questions: pastikan ada kolom score
        if (! Schema::hasColumn('questions', 'score')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->integer('score')->default(1)->after('payload');
            });
        }

        // answers: pastikan ada is_correct dan deleted_at
        if (! Schema::hasColumn('answers', 'is_correct')) {
            Schema::table('answers', function (Blueprint $table) {
                $table->boolean('is_correct')->default(false)->after('text');
            });
        }

        if (! Schema::hasColumn('answers', 'deleted_at')) {
            Schema::table('answers', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // pindahkan kunci jawaban lama (huruf) ke is_correct sebelum kolom lama dibuang
        if (Schema::hasColumn('answers', 'letter') && Schema::hasColumn('questions', 'correct_answer')) {
            DB::statement(
                'UPDATE answers a
                 JOIN questions q ON q.id = a.question_id
                 SET a.is_correct = 1
                 WHERE a.letter = q.correct_answer'
            );
        }

        // buang kolom lama yang sudah tidak dipakai
        if (Schema::hasColumn('answers', 'letter')) {
            Schema::table('answers', function (Blueprint $table) {
                $table->dropColumn('letter');
            });
        }

        if (Schema::hasColumn('questions', 'correct_answer')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropColumn('correct_answer');
            });
        }
    }

    public function down(): void
    {
        // sengaja kosong: migration ini hanya menyamakan struktur
    }
};