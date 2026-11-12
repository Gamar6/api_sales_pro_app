<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('previous_data');
            $table->json('correction_data');
            $table->text('reason');
            $table->string('status', 20)->default('approved');
            $table->timestamp('approved_at');
            $table->string('idempotency_key', 128);
            $table->string('idempotency_hash', 64);
            $table->timestamps();

            $table->unique(
                ['user_id', 'idempotency_key'],
                'visit_corrections_user_idempotency_unique'
            );
            $table->index(['visit_report_id', 'status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_corrections');
    }
};
