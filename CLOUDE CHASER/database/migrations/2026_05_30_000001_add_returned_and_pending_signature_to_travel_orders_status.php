<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE travel_orders MODIFY COLUMN status ENUM('draft','submitted','pending_signature','pending_release','issued','active','returned','completed') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE travel_orders MODIFY COLUMN status ENUM('draft','submitted','pending_release','issued','active','completed') NOT NULL DEFAULT 'draft'");
    }
};
