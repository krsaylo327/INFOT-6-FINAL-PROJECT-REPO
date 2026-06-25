<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itineraries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_request_id')->constrained()->cascadeOnDelete();
            $table->string('departure_place')->nullable();
            $table->string('arrival_place')->nullable();
            $table->dateTime('departure_at')->nullable();
            $table->dateTime('return_at')->nullable();
            $table->string('transport_mode')->nullable();
            $table->string('accommodation')->nullable();
            $table->decimal('daily_allowance', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('draft'); // draft, confirmed, completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itineraries');
    }
};
