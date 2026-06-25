<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endorsement_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dean_id')->constrained('users')->cascadeOnDelete();
            $table->enum('category', ['academic', 'research']);

            // Letter content
            $table->text('reason_for_endorsing');     // why dean can't attend
            $table->text('justification');             // why these staff are right people
            $table->text('expected_outcomes');         // what UA gains

            // Budget/funding
            $table->string('budget_code', 50)->nullable();
            $table->string('grant_account', 50)->nullable();
            $table->string('grant_title')->nullable();
            $table->decimal('estimated_cost', 12, 2)->default(0);

            // Workflow
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_remarks')->nullable();

            $table->timestamps();

            $table->index(['status', 'category']);
            $table->index('dean_id');
            $table->index('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('endorsement_letters');
    }
};
