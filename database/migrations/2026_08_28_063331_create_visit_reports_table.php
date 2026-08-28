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
            
            // Relasi 1-to-1 ke Tabel store_visits
            $table->foreignId('store_visit_id')
                  ->constrained('store_visits')
                  ->cascadeOnDelete();
            
            // Nama PIC / Penanggung Jawab Outlet
            $table->string('pic_name');
            
            // Menampung Array Aktivitas (Checkbox), e.g., ["Cek", "Visit", "Lain-lain: Pasang Banner"]
            $table->json('activities');
            
            // Sisa Stok (Dapat diisi persen atau pcs, atau keduanya)
            $table->unsignedInteger('stock_percentage')->nullable();
            $table->unsignedInteger('stock_pcs')->nullable();
            
            // Catatan Tambahan Kunjungan
            $table->text('notes')->nullable();
            
            // Menampung Array Path Foto (Maksimal 4 Foto, e.g., ["reports/photo1.jpg", "reports/photo2.jpg"])
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