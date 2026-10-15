<?php

namespace App\Filament\Resources\Questions\Schemas;

use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns()
                    ->columnSpanFull()
                    ->schema([
                        Select::make('subject_id')
                            ->label('Mata Pelajaran')
                            ->relationship('subject', 'name')
                            ->required(),

                        TextInput::make('score')
                            ->label('Bobot Nilai')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(0),

                        RichEditor::make('payload')
                            ->label('Pertanyaan')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('question-images')
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Keterangan tambahan')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->inline(false)
                            ->default(true)
                            ->required(),
                    ]),

                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('answers')
                            ->relationship()
                            ->label('Pilihan Jawaban')
                            ->addActionLabel('Tambah pilihan baru')
                            ->reorderable(false)
                            ->columns(2)
                            ->minItems(2)
                            ->maxItems(6)
                            ->rules([
                                fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                    $jumlahBenar = collect($value)
                                        ->filter(fn ($item) => ! empty($item['is_correct']))
                                        ->count();

                                    if ($jumlahBenar !== 1) {
                                        $fail('Harus ada tepat satu pilihan yang ditandai sebagai jawaban benar.');
                                    }
                                },
                            ])
                            ->schema([
                                TextInput::make('text')
                                    ->label('Deskripsi pilihan')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Toggle::make('is_active')
                                    ->label('Tersedia')
                                    ->default(true),

                                Toggle::make('is_correct')
                                    ->label('Jawaban benar')
                                    ->default(false),
                            ]),
                    ]),
            ]);
    }
}