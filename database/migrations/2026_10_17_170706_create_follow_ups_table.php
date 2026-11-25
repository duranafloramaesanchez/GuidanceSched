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
            // Follow-ups are part of the case history and are removed with their parent case.
            $table->foreignId('case_record_id')->constrained('case_records')->cascadeOnDelete();
            // Retain the author account while notes depend on it so historical attribution is not lost.
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
