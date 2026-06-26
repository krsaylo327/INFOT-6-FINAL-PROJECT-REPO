<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agreement_versions', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by_id')->nullable()->after('uploaded_by');
        });

        // Attempt best-effort backfill by matching `uploaded_by` name to users.name
        try {
            DB::statement('UPDATE agreement_versions av JOIN users u ON av.uploaded_by = u.name SET av.uploaded_by_id = u.id');
        } catch (Throwable $e) {
            // If the DB driver doesn't support JOIN in UPDATE (e.g., sqlite), perform a PHP-side backfill
            $rows = DB::table('agreement_versions')->select('id', 'uploaded_by')->get();
            foreach ($rows as $row) {
                if (empty($row->uploaded_by)) {
                    continue;
                }

                $user = DB::table('users')->where('name', $row->uploaded_by)->first();
                if ($user) {
                    DB::table('agreement_versions')->where('id', $row->id)->update(['uploaded_by_id' => $user->id]);
                }
            }
        }

        // Add foreign key if possible
        Schema::table('agreement_versions', function (Blueprint $table) {
            $table->foreign('uploaded_by_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agreement_versions', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by_id']);
            $table->dropColumn('uploaded_by_id');
        });
    }
};
