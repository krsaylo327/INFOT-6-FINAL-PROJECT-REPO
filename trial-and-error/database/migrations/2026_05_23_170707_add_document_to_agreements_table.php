<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('agreements', 'document')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->string('document')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('agreements', 'document')) {
            Schema::table('agreements', function (Blueprint $table) {
                $table->dropColumn('document');
            });
        }
    }
};
