<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_targets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();

            $table->string('target_type', 30); // all, room, tenant
            $table->unsignedBigInteger('target_id')->nullable();

            $table->timestamps();

            $table->index(['announcement_id', 'target_type']);
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_targets');
    }
};