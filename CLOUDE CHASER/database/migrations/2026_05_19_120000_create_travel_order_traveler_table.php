<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_order_traveler', function (Blueprint $table) {
            $table->foreignId('travel_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['travel_order_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_order_traveler');
    }
};
