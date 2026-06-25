<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->string('receipt_timing')->default('before_travel')->after('status')
                  ->comment('before_travel | after_travel');
        });
    }
    public function down(): void {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropColumn('receipt_timing');
        });
    }
};
