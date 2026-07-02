<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('notify_on_expiration')->default(true);
            $table->timestamps();
            $table->unique(['agreement_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_subscriptions');
    }
};
