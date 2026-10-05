<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'TSI-2025' => 'Tecnologia em Sistemas para Internet (TSI)',
            'LOG' => 'Logística',
            'IPI' => 'Informática para Internet (IPI)',
            'ADM' => 'Administração',
            'GQ' => 'Gestão da Qualidade',
        ] as $code => $name) {
            DB::table('courses')->updateOrInsert(['code' => $code], [
                'name' => $name,
                'is_active' => true,
                'updated_at' => now(),
                'id' => DB::table('courses')->where('code', $code)->value('id') ?? (string) Str::uuid(),
                'created_at' => DB::table('courses')->where('code', $code)->value('created_at') ?? now(),
            ]);
        }
    }
}
