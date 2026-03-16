<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\RoomType;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $types = RoomType::pluck('id', 'name');

        $rooms = [
            ['code'=>'101','floor'=>1,'room_type_id'=>$types['ห้องแอร์ เตียงคู่'],'price_monthly'=>3500,'status'=>'vacant'],
            ['code'=>'102','floor'=>1,'room_type_id'=>$types['ห้องแอร์ เตียงเดี่ยว'],'price_monthly'=>3200,'status'=>'occupied'],
            ['code'=>'103','floor'=>1,'room_type_id'=>$types['ห้องพัดลม เตียงคู่'],'price_monthly'=>2500,'status'=>'vacant'],
            ['code'=>'201','floor'=>2,'room_type_id'=>$types['ห้องแอร์ เตียงคู่'],'price_monthly'=>3700,'status'=>'maintenance'],
            ['code'=>'202','floor'=>2,'room_type_id'=>$types['ห้องพัดลม เตียงเดี่ยว'],'price_monthly'=>2200,'status'=>'vacant'],
            ['code'=>'203','floor'=>2,'room_type_id'=>$types['ห้องแอร์ เตียงเดี่ยว'],'price_monthly'=>3300,'status'=>'occupied'],
        ];

        foreach ($rooms as $room) {
            Room::updateOrCreate(
                ['code'=>$room['code']],
                $room
            );
        }
    }
}