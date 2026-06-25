<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('received_invitations', function (Blueprint $table) {
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete()->after('received_by');
        });
    }

    public function down(): void
    {
        Schema::table('received_invitations', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'logged_by');
            $table->dropColumn('logged_by');
        });
    }
};
