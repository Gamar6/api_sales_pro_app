<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_retention_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odoo_id');
            $table->date('snapshot_date');
            
            // Data Perubahan Retensi
            $table->date('last_order_date')->nullable();
            $table->integer('days_since')->default(0);
            $table->integer('weeks_since')->default(0);
            $table->string('retensi_status')->nullable();
            $table->string('aktif_group')->nullable();
            $table->float('avg_retensi_weeks')->default(0);
            $table->float('gap_vs_average')->default(0);
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->integer('priority')->default(99);

            $table->timestamps();

            
            $table->foreign('odoo_id')->references('odoo_id')->on('stores')->onDelete('cascade');
            
            $table->unique(['odoo_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_retention_histories');
    }
};