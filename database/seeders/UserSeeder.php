<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
  public function run()
  {
    User::create([
      'name' => 'Catarina Silva',
      'email' => 'catarina.silva@example.com',
      'email_verified_at' => now(),
      'password' => Hash::make('password'),
      'cpf' => '44556677880',
      'phone' => '11966665555',
      'user_type' => 'student',
    ]);

    User::create([
      'name' => 'Dylan Borges',
      'email' => 'dylan.borges@example.com',
      'email_verified_at' => now(),
      'password' => Hash::make('password'),
      'cpf' => '55667788990',
      'phone' => '11955554444',
      'user_type' => 'student',
    ]);

    User::create([
      'name' => 'Izabelle Alves',
      'email' => 'izabelle.alves@example.com',
      'email_verified_at' => now(),
      'password' => Hash::make('password'),
      'cpf' => '66778899000',
      'phone' => '11944443333',
      'user_type' => 'student',
    ]);

    User::create([
      'name' => 'Laura Esterfani',
      'email' => 'laura.esterfani@example.com',
      'email_verified_at' => now(),
      'password' => Hash::make('password'),
      'cpf' => '77889900110',
      'phone' => '11933332222',
      'user_type' => 'student',
    ]);

    User::create([
      'name' => 'Keila Isabelle',
      'email' => 'keila.izabelle@example.com',
      'email_verified_at' => now(),
      'password' => Hash::make('password'),
      'cpf' => '88990011220',
      'phone' => '11922221111',
      'user_type' => 'student',
    ]);

    User::create([
      'name' => 'Rubens Lira',
      'email' => 'rubens.lira@example.com',
      'email_verified_at' => now(),
      'password' => Hash::make('password'),
      'cpf' => '22334455660',
      'phone' => '11988887777',
      'user_type' => 'student',
    ]);

    User::create([
      'name' => 'Victor Boy de Izabelle',
      'email' => 'victor.gustavo@example.com',
      'email_verified_at' => now(),
      'password' => Hash::make('password'),
      'cpf' => '33445566770',
      'phone' => '11977776666',
      'user_type' => 'student',
    ]);
  }
}
