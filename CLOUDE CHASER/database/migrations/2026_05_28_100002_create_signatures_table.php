<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signer_id')->constrained('users')->cascadeOnDelete();
            $table->string('signable_type');             // App\Models\EndorsementLetter, TravelOrder, etc.
            $table->unsignedBigInteger('signable_id');
            $table->string('purpose');                   // 'endorsement_review', 'to_issuance', 'travel_approval'
            $table->string('signature_image_path');      // Snapshot of signer's signature at time of signing
            $table->string('document_hash', 64);         // SHA-256 of document key fields
            $table->string('verification_code', 16)->unique(); // Used in public verification URL/QR
            $table->string('signer_name_snapshot');      // Snapshot of signer's name at time of signing
            $table->string('signer_position_snapshot')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('decision_remarks')->nullable();
            $table->string('decision', 20)->nullable();  // approved | rejected | issued
            $table->timestamp('signed_at');
            $table->timestamps();

            $table->index(['signable_type', 'signable_id']);
            $table->index('verification_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
