<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            // Dated notes and actions recorded after a guidance case is created.
            $table->id();
            $table->foreignId('case_record_id')->constrained('case_records')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->date('follow_up_date');
            $table->text('notes');
            $table->string('action_taken')->nullable();
            $table->timestamps();
            $table->index('created_by');
            $table->index(['case_record_id', 'follow_up_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
