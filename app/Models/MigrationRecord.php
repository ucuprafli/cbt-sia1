<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationRecord extends Model
{
    // Nama kelasnya sengaja "MigrationRecord", bukan "Migration",
    // supaya tidak bentrok dengan class bawaan Laravel
    // Illuminate\Database\Migrations\Migration.
    protected $table = 'migrations';

    // Tabel bawaan Laravel ini tidak punya kolom created_at/updated_at.
    public $timestamps = false;

    protected $fillable = [
        'migration',
        'batch',
    ];

    protected function casts(): array
    {
        return [
            'batch' => 'integer',
        ];
    }
}