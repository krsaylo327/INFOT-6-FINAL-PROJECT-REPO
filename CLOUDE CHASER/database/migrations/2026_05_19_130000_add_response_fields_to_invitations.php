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
            DB::statement("ALTER TABLE invitations MODIFY COLUMN status ENUM('open','accepted','rejected','acted') NOT NULL DEFAULT 'open'");
        }

        Schema::table('invitations', function (Blueprint $table) {
            $table->text('reject_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn('reject_reason');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE invitations MODIFY COLUMN status ENUM('open','acted') NOT NULL DEFAULT 'open'");
        }
    }
};
