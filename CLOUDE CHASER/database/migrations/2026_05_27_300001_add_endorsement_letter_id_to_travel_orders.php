<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->foreignId('endorsement_letter_id')
                  ->nullable()
                  ->after('travel_request_id')
                  ->constrained('endorsement_letters')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropForeign(['endorsement_letter_id']);
            $table->dropColumn('endorsement_letter_id');
        });
    }
};
