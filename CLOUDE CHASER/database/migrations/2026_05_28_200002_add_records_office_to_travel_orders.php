<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->foreignId('records_released_by')->nullable()->constrained('users')->nullOnDelete()->after('issued_at');
            $table->timestamp('records_released_at')->nullable()->after('records_released_by');
            $table->text('records_remarks')->nullable()->after('records_released_at');
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'records_released_by');
            $table->dropColumn(['records_released_by', 'records_released_at', 'records_remarks']);
        });
    }
};
