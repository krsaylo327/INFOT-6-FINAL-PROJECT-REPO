<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'college_personnel')
            ->update(['role' => 'authorized_personnel']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'authorized_personnel')
            ->update(['role' => 'college_personnel']);
    }
};
