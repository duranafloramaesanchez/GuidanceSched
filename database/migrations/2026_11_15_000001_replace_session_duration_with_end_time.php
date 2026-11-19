<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_records', function (Blueprint $table) {
            $table->dropColumn('session_duration_minutes');
            $table->time('session_end_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('case_records', function (Blueprint $table) {
            $table->dropColumn('session_end_time');
            $table->unsignedSmallInteger('session_duration_minutes')->nullable();
        });
    }
};
