<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cleaning_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();

            $table->text('note')->nullable();
            $table->date('schedule_date')->nullable();
            $table->enum('status', ['submitted', 'in_progress', 'done', 'rejected'])->default('submitted');
            $table->timestamps();

            $table->index(['status', 'schedule_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cleaning_requests');
    }
};
