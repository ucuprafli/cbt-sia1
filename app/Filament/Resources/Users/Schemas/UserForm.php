<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        // upload foto
                        FileUpload::make('photo_path')
                            ->label('Upload foto profil')
                            ->hiddenLabel()
                            ->image()   // khusus upload gambar
                            ->avatar()  // resize otomatis dan circular
                            ->disk('public')    // partisi storage
                            ->directory('user-photos')  // nama folder
                            ->maxSize(1024) // ukuran max 1MB
                            ->imageEditor()
                            ->columnSpanFull()
                            ->alignCenter(),
                        TextInput::make('name')
                            ->label('Nama lengkap')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('email')
                            ->label('Alamat email')
                            ->unique('users', 'email')
                            ->prefix('@')
                            ->email(),
                        TextInput::make('username')
                            ->label('Login username')
                            // harus unique dengan user yang lain
                            ->unique(
                                table: 'users',
                                column: 'username',
                            )
                            ->placeholder('Digunakan untuk login akun')
                            ->helperText('Username harus unik.')
                            ->required(),
                        TextInput::make('phone')
                            ->label('Nomor telepon')
                            // ->prefixIcon('heroicon-o-phone')
                            ->prefixIcon(Heroicon::OutlinedPhone)
                            ->tel(),

                        TextInput::make('password')
                            // disembunyikan di halaman edit
                            ->hiddenOn('edit')
                            // tampilkan password
                            ->revealable()
                            ->password()
                            ->required(),
                    ]),
            ]);
    }
}