<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            // 'official' = dean-assigned/endorsed; 'personal' = traveler self-requests, dean only noted
            $table->enum('initiation_type', ['official', 'personal'])
                  ->default('official')
                  ->after('invitation_id');

            // For personal TOs, dean_id is nullable (dean is just noted, not required to endorse)
            $table->foreignId('noted_by')->nullable()->after('dean_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropColumn('initiation_type');
            $table->dropForeignIdFor(\App\Models\User::class, 'noted_by');
            $table->dropColumn('noted_by');
        });
    }
};
