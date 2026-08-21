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
    Schema::create('retentions', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('partner_id')->unique();
        $table->string('partner_name')->nullable();
        $table->string('kota')->nullable();
        $table->string('phone_clean')->nullable();
        $table->string('sales_name')->nullable();
        $table->date('last_order_date')->nullable();
        $table->decimal('total_sales_2024_plus', 15, 2)->default(0);
        $table->integer('days_since')->default(0);
        $table->integer('weeks_since')->default(0);
        $table->string('retensi_status')->nullable();
        $table->float('avg_retensi_weeks')->nullable();
        $table->float('gap_vs_average')->nullable();
        $table->string('aktif_group')->nullable();
        $table->integer('priority')->default(99);
        $table->string('delivery_route')->nullable();
        $table->date('snapshot_date')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retentions');
    }
};
