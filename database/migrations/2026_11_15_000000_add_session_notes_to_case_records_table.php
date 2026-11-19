<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_records', function (Blueprint $table) {
            $table->text('remarks')->nullable();
            $table->unsignedSmallInteger('session_duration_minutes')->nullable();
            $table->date('session_date')->nullable();
            $table->time('session_time')->nullable();
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('case_records', function (Blueprint $table) {
            $table->dropColumn([
                'remarks',
                'session_duration_minutes',
                'session_date',
                'session_time',
                'findings',
                'recommendations',
            ]);
        });
    }
};
