<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'ห้องแอร์ เตียงคู่',
            'ห้องแอร์ เตียงเดี่ยว',
            'ห้องพัดลม เตียงคู่',
            'ห้องพัดลม เตียงเดี่ยว',
        ];

        foreach ($types as $name) {
            DB::table('room_types')->updateOrInsert(
                ['name' => $name],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
