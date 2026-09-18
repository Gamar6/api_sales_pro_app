<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_reports', function (Blueprint $table) {
            $table->decimal('sales_latitude', 10, 7)
                ->nullable()
                ->after('photos');

            $table->decimal('sales_longitude', 10, 7)
                ->nullable()
                ->after('sales_latitude');

            $table->decimal('sales_accuracy', 8, 2)
                ->nullable()
                ->after('sales_longitude');

            $table->decimal('distance_from_store', 10, 2)
                ->nullable()
                ->after('sales_accuracy');

            $table->boolean('is_outside_radius')
                ->nullable()
                ->after('distance_from_store');

            $table->timestamp('location_captured_at')
                ->nullable()
                ->after('is_outside_radius');
        });
    }

    public function down(): void
    {
        Schema::table('visit_reports', function (Blueprint $table) {
            $table->dropColumn([
                'sales_latitude',
                'sales_longitude',
                'sales_accuracy',
                'distance_from_store',
                'is_outside_radius',
                'location_captured_at',
            ]);
        });
    }
};
