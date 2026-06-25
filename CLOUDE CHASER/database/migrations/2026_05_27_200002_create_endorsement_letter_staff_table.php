<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endorsement_letter_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endorsement_letter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('position')->nullable();         // e.g. "Associate Professor"
            $table->string('role_in_event')->nullable();    // e.g. "Speaker", "Attendee", "Panelist"
            $table->timestamp('notified_at')->nullable();   // when staff was first notified
            $table->timestamps();

            $table->unique(['endorsement_letter_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endorsement_letter_staff');
    }
};
