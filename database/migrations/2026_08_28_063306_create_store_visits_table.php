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
        Schema::create('store_visits', function (Blueprint $table) {
            $table->id();
            
            // ID Toko/Partner dari Database Odoo
            $table->unsignedBigInteger('odoo_partner_id');
            
            // Relasi ke User/Sales di Laravel
            $table->foreignId('sales_id')->constrained('users')->cascadeOnDelete();
            
            // Tanggal Kunjungan (YYYY-MM-DD) untuk penguncian harian
            $table->date('visit_date');
            
            // Status Siklus Kunjungan
            $table->enum('status', ['IN_VISIT', 'COMPLETED', 'CANCELLED'])->default('IN_VISIT');
            
            // Waktu Check-in (Claim) dan Check-out (Submit Form)
            $table->timestamp('check_in_at');
            $table->timestamp('check_out_at')->nullable();
            
            $table->timestamps();

            // Indexing untuk Performa Query Pengecekan Harian & History
            $table->index(['odoo_partner_id', 'visit_date']);
            $table->index(['sales_id', 'visit_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_visits');
    }
};