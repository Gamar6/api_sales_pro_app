<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\CarbonImmutable;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_visits', function (Blueprint $table) {
            $table->enum('store_type', ['regular', 'field'])
                ->default('regular')
                ->after('odoo_partner_id');
            $table->foreignId('field_store_id')
                ->nullable()
                ->after('store_type')
                ->constrained('field_stores')
                ->restrictOnDelete();
            $table->unsignedBigInteger('odoo_partner_id')
                ->nullable()
                ->change();
        });

        DB::table('visit_reports')
            ->where('store_type', 'field')
            ->whereNull('store_visit_id')
            ->whereNotNull('store_id')
            ->whereNotNull('user_id')
            ->whereNotNull('visited_at')
            ->orderBy('id')
            ->chunkById(100, function ($reports): void {
                foreach ($reports as $report) {
                    $store = DB::table('field_stores')
                        ->where('id', $report->store_id)
                        ->where('user_id', $report->user_id)
                        ->first();

                    if (!$store) {
                        continue;
                    }

                    $visitedAt = CarbonImmutable::parse($report->visited_at);
                    $now = now();
                    $storeVisitId = DB::table('store_visits')->insertGetId([
                        'store_type' => 'field',
                        'field_store_id' => $store->id,
                        'odoo_partner_id' => null,
                        'sales_id' => $report->user_id,
                        'visit_date' => $visitedAt->toDateString(),
                        'status' => 'COMPLETED',
                        'active_store_key' => null,
                        'active_sales_key' => null,
                        'check_in_at' => $visitedAt,
                        'check_out_at' => $visitedAt,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('visit_reports')
                        ->where('id', $report->id)
                        ->update(['store_visit_id' => $storeVisitId]);
                }
            });
    }

    public function down(): void
    {
        if (DB::table('store_visits')->where('store_type', 'field')->exists()) {
            throw new RuntimeException(
                'Cannot roll back field store support while field store visits exist.'
            );
        }

        Schema::table('store_visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('field_store_id');
            $table->dropColumn('store_type');
            $table->unsignedBigInteger('odoo_partner_id')
                ->nullable(false)
                ->change();
        });
    }
};
