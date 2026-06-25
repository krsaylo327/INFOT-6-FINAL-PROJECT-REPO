<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('received_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->string('sender_org');
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('sender_phone')->nullable();
            $table->string('event_name');
            $table->string('event_venue')->nullable();
            $table->string('event_destination')->nullable();
            $table->date('event_date_from')->nullable();
            $table->date('event_date_to')->nullable();
            $table->enum('event_type', ['academic', 'research'])->nullable();
            $table->text('description')->nullable();
            $table->date('received_at');
            $table->enum('status', ['new', 'forwarded', 'declined'])->default('new');
            $table->text('declined_reason')->nullable();
            $table->timestamps();

            $table->index(['received_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('received_invitations');
    }
};
