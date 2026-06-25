<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_policy_no')->nullable();
            $table->date('insurance_valid_until')->nullable();
            $table->boolean('waiver_required')->default(false);
            $table->boolean('waiver_acknowledged')->default(false);
            $table->timestamp('waiver_acknowledged_at')->nullable();
        });

        Schema::create('travel_order_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_order_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', ['waiver', 'receipt', 'other'])->default('other');
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index(['travel_order_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_order_attachments');

        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropColumn([
                'insurance_provider',
                'insurance_policy_no',
                'insurance_valid_until',
                'waiver_required',
                'waiver_acknowledged',
                'waiver_acknowledged_at',
            ]);
        });
    }
};
