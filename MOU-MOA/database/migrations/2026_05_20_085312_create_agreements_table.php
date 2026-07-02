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
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['MOA', 'MOU']);
            $table->string('partner_organization');
            $table->text('description')->nullable();
            $table->date('signed_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('status')->default('draft');
            $table->string('workflow_status')->nullable();
            $table->string('current_handler')->nullable();
            $table->foreignId('submitted_by')->nullable();
            $table->string('document')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
