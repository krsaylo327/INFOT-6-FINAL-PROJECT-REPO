<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->text('return_report')->nullable()->after('returned_at');
            $table->foreignId('returned_by')->nullable()->after('return_report')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropForeign(['returned_by']);
            $table->dropColumn(['return_report', 'returned_by']);
        });
    }
};
