<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            // self  = traveler requested it themselves
            // assigned = approver/admin assigned it to the traveler
            $table->string('type')->default('self')->after('status');

            $table->foreignId('assigned_by')
                ->nullable()
                ->after('type')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('acknowledged_at')->nullable()->after('assigned_by');
        });
    }

    public function down(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropColumn(['type', 'acknowledged_at']);
        });
    }
};
