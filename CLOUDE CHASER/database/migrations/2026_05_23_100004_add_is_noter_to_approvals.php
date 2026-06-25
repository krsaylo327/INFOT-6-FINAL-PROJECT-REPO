<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('approvals', function (Blueprint $table) {
            $table->boolean('is_noter')->default(false)->after('level')
                  ->comment('True for Research Director noting step — not a full approve/reject');
        });
    }
    public function down(): void {
        Schema::table('approvals', function (Blueprint $table) {
            $table->dropColumn('is_noter');
        });
    }
};
