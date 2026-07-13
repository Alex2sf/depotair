<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Illuminate\Validation\Rules\Password;
class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                ->label('Nama Lengkap')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->unique(ignoreRecord: true)
                ->required()
                ->maxLength(255),

            TextInput::make('password')
                ->label(fn (string $context) => $context === 'edit'
                    ? 'Password Baru (kosongkan jika tidak diganti)'
                    : 'Password')
                ->password()
                ->nullable()
                ->rules(fn (string $context) => $context === 'create' ? ['confirmed'] : [])
                ->rule(Password::default())
                ->dehydrated(fn ($state) => filled($state))
                ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                ->required(fn (string $context) => $context === 'create'),

            TextInput::make('password_confirmation')
                ->password()
                ->label('Konfirmasi Password')
                ->dehydrated(false)
                ->visible(fn (string $context) => $context === 'create'),

            Select::make('role')
                ->label('Role System (Database)')
                ->options([
                    'admin' => 'Admin',
                    'kasir' => 'Kasir',
                    'kurir' => 'Kurir',
                    'owner' => 'Owner',
                ])
                ->required()
                ->native(false),

            Select::make('roles')
                ->label('Role')
                ->multiple()
                ->relationship('roles', 'name')
                ->preload()
                ->searchable()
                ->required(),
            ]);
    }
}
