<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE travel_orders MODIFY COLUMN status ENUM('draft','submitted','pending_release','issued','active','completed') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE travel_orders MODIFY COLUMN status ENUM('draft','submitted','issued','active','completed') NOT NULL DEFAULT 'draft'");
        }
    }
};
