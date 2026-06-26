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
        Schema::table('agreements', function (Blueprint $table) {

            if (! Schema::hasColumn('agreements', 'workflow_status')) {
                $table->string('workflow_status')->default('draft');
            }

            if (! Schema::hasColumn('agreements', 'current_handler')) {
                $table->string('current_handler')->nullable();
            }

            if (! Schema::hasColumn('agreements', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {

            $table->dropColumn('workflow_status');
            $table->dropColumn('current_handler');
            $table->dropColumn('submitted_by');

        });
    }
};
