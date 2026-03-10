<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('type', 50); // parcel_arrived, announcement, urgent_announcement
            $table->string('title', 190);
            $table->text('message')->nullable();

            $table->string('ref_type', 50)->nullable(); // parcel, announcement
            $table->unsignedBigInteger('ref_id')->nullable();

            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['ref_type', 'ref_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};