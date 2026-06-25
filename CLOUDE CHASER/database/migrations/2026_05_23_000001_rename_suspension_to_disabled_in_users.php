<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename columns
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('suspended_at', 'disabled_at');
            $table->renameColumn('suspension_reason', 'disable_reason');
            $table->renameColumn('suspended_by', 'disabled_by');
        });

        // Update status value
        DB::table('users')->where('status', 'suspended')->update(['status' => 'disabled']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('disabled_at', 'suspended_at');
            $table->renameColumn('disable_reason', 'suspension_reason');
            $table->renameColumn('disabled_by', 'suspended_by');
        });

        DB::table('users')->where('status', 'disabled')->update(['status' => 'suspended']);
    }
};
