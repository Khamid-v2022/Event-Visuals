<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->timestamp('confirmation_sent_at')->nullable();
            $table->timestamp('reminder_three_days_sent_at')->nullable();
            $table->timestamp('reminder_one_day_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'event_id'], 'event_attendances_pair_unique');
            $table->index(['user_id', 'created_at'], 'event_attendances_user_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_attendances');
    }
};
