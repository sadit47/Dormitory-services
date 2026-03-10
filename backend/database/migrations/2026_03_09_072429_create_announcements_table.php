<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();

            $table->string('title', 190);
            $table->longText('content');

            $table->string('type', 30)->default('general'); // general, urgent, maintenance
            $table->string('status', 30)->default('draft'); // draft, published, expired

            $table->boolean('is_pinned')->default(false);

            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();

            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['status', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};