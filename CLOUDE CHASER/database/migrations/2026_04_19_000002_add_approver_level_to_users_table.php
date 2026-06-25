<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1 = Immediate supervisor / Dean
            // 2 = Finance
            // 3 = VP / President
            // Null for travelers / admins that don't act as approvers.
            $table->unsignedTinyInteger('approver_level')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('approver_level');
        });
    }
};
