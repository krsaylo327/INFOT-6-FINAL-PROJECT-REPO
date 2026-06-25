<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_order_id')->nullable()->constrained('travel_orders')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('vehicle_type')->default('university_vehicle')
                  ->comment('university_vehicle | rental | public_transport | other');
            $table->string('pickup_location');
            $table->datetime('pickup_datetime');
            $table->datetime('return_datetime');
            $table->unsignedSmallInteger('passenger_count')->default(1);
            $table->text('notes')->nullable();
            $table->string('status')->default('pending')
                  ->comment('pending | approved | denied');
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('denial_reason')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('vehicle_requests');
    }
};
