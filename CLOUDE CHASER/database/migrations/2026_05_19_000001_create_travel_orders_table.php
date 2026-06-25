<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_orders', function (Blueprint $table) {
            $table->id();
            $table->string('to_number')->nullable()->unique();
            $table->enum('type', ['academic', 'research']);
            $table->foreignId('traveler_id')->constrained('users');
            $table->foreignId('dean_id')->constrained('users');
            $table->foreignId('department_id')->constrained('departments');
            $table->string('event_name');
            $table->string('destination');
            $table->string('venue');
            $table->date('date_from');
            $table->date('date_to');
            $table->text('purpose');
            $table->boolean('has_students')->default(false);
            $table->unsignedSmallInteger('student_count')->nullable();
            $table->enum('status', ['draft', 'submitted', 'issued'])->default('draft');
            $table->foreignId('issued_by')->nullable()->constrained('users');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_orders');
    }
};
