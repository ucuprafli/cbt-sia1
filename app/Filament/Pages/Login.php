<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
use Override;

class Login extends BaseLogin
{
    // Method ini untuk menentukan kolom apa yang akan dibaca
    // jika input dalam format email, maka kolom yang di cari adalah email
    protected function getCredentialsFromFormData(array $data): array
    {
        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';

        return [
            $field => $data['login'],
            'password' => $data['password'],
        ];
    }

    // rancangan form field
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('login')
                    ->label('Email atau username')
                    ->required(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    // tampilkan pesan error
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => 'Data login salah!',
        ]);
    }

}
