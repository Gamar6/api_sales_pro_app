<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('store_name');
            $table->string('owner_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->text('address');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('photo_url');
            $table->timestamps();
        });

        Schema::table('visit_reports', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('store_type', ['regular', 'field'])->nullable()->after('user_id');
            $table->unsignedBigInteger('store_id')->nullable()->after('store_type');
            $table->string('check_in_photo')->nullable()->after('notes');
            $table->decimal('check_in_lat', 10, 8)->nullable();
            $table->decimal('check_in_lng', 10, 8)->nullable();
            $table->timestamp('visited_at')->nullable();

            $table->unsignedBigInteger('store_visit_id')->nullable()->change();
            $table->string('pic_name')->nullable()->change();
            $table->json('activities')->nullable()->change();
            $table->json('photos')->nullable()->change();
        });

        $reportsWithSales = DB::table('visit_reports')
            ->join('store_visits', 'visit_reports.store_visit_id', '=', 'store_visits.id')
            ->whereNull('visit_reports.user_id')
            ->get([
                'visit_reports.id',
                'store_visits.sales_id',
            ]);

        foreach ($reportsWithSales as $report) {
            DB::table('visit_reports')
                ->where('id', $report->id)
                ->update(['user_id' => $report->sales_id]);
        }
    }

    public function down(): void
    {
        DB::table('visit_reports')->whereNull('store_visit_id')->delete();

        Schema::table('visit_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('store_visit_id')->nullable(false)->change();
            $table->string('pic_name')->nullable(false)->change();
            $table->json('activities')->nullable(false)->change();
            $table->json('photos')->nullable(false)->change();

            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'store_type',
                'store_id',
                'check_in_photo',
                'check_in_lat',
                'check_in_lng',
                'visited_at',
            ]);
        });

        Schema::dropIfExists('field_stores');
    }
};
