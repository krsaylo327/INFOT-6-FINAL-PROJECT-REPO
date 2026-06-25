<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expand the status column to allow 'endorsed' value alongside existing statuses.
        // invitations.status: open | accepted | rejected | endorsed | acted
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE invitations MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'open'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE invitations MODIFY COLUMN status ENUM('open','accepted','rejected','acted') NOT NULL DEFAULT 'open'");
        }
    }
};
