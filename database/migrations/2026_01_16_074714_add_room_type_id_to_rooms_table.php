<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignId('room_type_id')
                ->nullable()
                ->after('floor')
                ->constrained('room_types')
                ->nullOnDelete();

            // ถ้าคุณจะ “เลิกใช้” rooms.type ในอนาคตค่อยลบทิ้งทีหลัง
            $table->index('room_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_type_id');
        });
    }
};
