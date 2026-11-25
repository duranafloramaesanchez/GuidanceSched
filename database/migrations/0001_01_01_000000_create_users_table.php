<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Every person who can sign in: student, teacher, or administrator.
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            // Controllers use this role to choose permissions and role-specific data.
            $table->string('role')->default('student');
            // Student-only fields stay nullable because staff accounts share this table.
            $table->string('student_number', 50)->nullable()->unique();
            $table->string('grade_level', 50)->nullable();
            $table->string('section', 100)->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_relationship', 100)->nullable();
            $table->string('guardian_contact_number', 50)->nullable();
            // Links a student to the teacher responsible for that student.
            // A self-reference links students to teachers; nullOnDelete keeps a student account if a teacher is removed.
            $table->foreignId('assigned_teacher_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->index('assigned_teacher_id');
            $table->timestamps();
        });

        // Framework support table used by Laravel's password-reset flow.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            // Stores browser sign-in sessions when SESSION_DRIVER is set to database.
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
