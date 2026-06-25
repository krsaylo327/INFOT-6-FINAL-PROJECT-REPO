<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE travel_orders MODIFY COLUMN status ENUM('draft','submitted','issued','active','completed') NOT NULL DEFAULT 'draft'");
        }

        Schema::table('travel_orders', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable()->after('issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropColumn('returned_at');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE travel_orders MODIFY COLUMN status ENUM('draft','submitted','issued') NOT NULL DEFAULT 'draft'");
        }
    }
};
