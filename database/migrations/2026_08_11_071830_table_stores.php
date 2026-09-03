<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odoo_id')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('sales_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->date('last_order_date')->nullable();
            $table->unsignedInteger('days_since')->default(0);
            $table->unsignedInteger('weeks_since')->default(0);
            $table->string('retensi_status')->nullable()->index();
            $table->string('aktif_group')->nullable();
            $table->decimal('avg_retensi_weeks', 8, 2)->default(0);
            $table->decimal('gap_vs_average', 8, 2)->default(0);
            $table->decimal('total_sales', 18, 2)->default(0);
            $table->unsignedInteger('priority')->default(99)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
