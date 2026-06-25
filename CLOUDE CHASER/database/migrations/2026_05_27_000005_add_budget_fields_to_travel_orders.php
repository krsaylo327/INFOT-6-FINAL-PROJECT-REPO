<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->string('budget_code', 50)->nullable();
            $table->string('grant_account', 50)->nullable();
            $table->string('grant_title')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropColumn(['budget_code', 'grant_account', 'grant_title']);
        });
    }
};
