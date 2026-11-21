<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            // A consultation request connects a student, the creator, and optionally assigned guidance staff.
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('reason')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable(); // Staff-only appointment notes.
            $table->timestamps();
            // Indexes make common student, status, and date searches faster.
            $table->index(['student_id', 'status']);
            $table->index(['status', 'scheduled_at']);
            $table->index('created_by');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
