<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();

            $table->string('tracking_no', 120)->nullable();
            $table->string('courier', 100)->nullable();
            $table->string('sender_name', 190)->nullable();
            $table->text('note')->nullable();

            $table->string('status', 30)->default('arrived'); // arrived, picked_up, cancelled

            $table->dateTime('received_at')->nullable();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('picked_up_at')->nullable();
            $table->foreignId('picked_up_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['room_id', 'status']);
            $table->index('tracking_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcels');
    }
};