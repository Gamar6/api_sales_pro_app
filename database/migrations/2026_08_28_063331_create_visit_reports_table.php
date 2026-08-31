<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visit_reports', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('store_visit_id')
                  ->constrained('store_visits')
                  ->cascadeOnDelete();
            
            $table->string('pic_name');
            
            $table->json('activities');
            
            $table->unsignedInteger('stock_percentage')->nullable();
            $table->unsignedInteger('stock_pcs')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->json('photos');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_reports');
    }
};