<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_by')->constrained('users');   // President
            $table->foreignId('assigned_to')->constrained('users'); // Dean
            $table->string('event_name');
            $table->string('destination')->nullable();
            $table->string('venue')->nullable();
            $table->enum('type', ['academic', 'research']);
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->text('details');
            $table->enum('status', ['open', 'acted'])->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
