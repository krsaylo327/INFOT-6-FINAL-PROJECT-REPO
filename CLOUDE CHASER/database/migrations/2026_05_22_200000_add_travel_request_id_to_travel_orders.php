<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->foreignId('travel_request_id')
                  ->nullable()
                  ->after('invitation_id')
                  ->constrained('travel_requests')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropForeign(['travel_request_id']);
            $table->dropColumn('travel_request_id');
        });
    }
};
