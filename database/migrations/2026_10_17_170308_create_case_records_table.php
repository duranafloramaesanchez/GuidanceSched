<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->string('case_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open');
            $table->string('category')->default('other');
            $table->text('remarks')->nullable();
            $table->date('session_date')->nullable();
            $table->time('session_time')->nullable();
            $table->time('session_end_time')->nullable();
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamps();
            $table->index(['status', 'category']);
            $table->index('student_id');
            $table->index('created_by');
            $table->index('appointment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_records');
    }
};
