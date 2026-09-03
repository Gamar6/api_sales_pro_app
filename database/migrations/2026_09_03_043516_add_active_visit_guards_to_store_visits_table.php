<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_visits', function (Blueprint $table) {
            $table->unsignedBigInteger('active_store_key')
                ->nullable()
                ->unique()
                ->after('status');
            $table->unsignedBigInteger('active_sales_key')
                ->nullable()
                ->unique()
                ->after('active_store_key');
        });
    }

    public function down(): void
    {
        Schema::table('store_visits', function (Blueprint $table) {
            $table->dropUnique(['active_store_key']);
            $table->dropUnique(['active_sales_key']);
            $table->dropColumn(['active_store_key', 'active_sales_key']);
        });
    }
};
