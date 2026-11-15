<?php

namespace Tests\Feature;

use App\Models\FieldStore;
use App\Models\User;
use App\Models\StoreVisit;
use App\Services\OdooService;
use App\Services\Odoo\OdooClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FieldVisitReportListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_field_visit_reports_appear_in_visit_logs_without_loading_odoo(): void
    {
        $sales = User::factory()->create(['status' => 'active']);
        $fieldStore = FieldStore::create([
            'user_id' => $sales->id,
            'store_name' => 'Toko Lapangan Sukses',
            'owner_name' => 'Ibu Sari',
            'phone_number' => '08123456789',
            'address' => 'Jl. Contoh No. 10',
            'latitude' => -6.2,
            'longitude' => 106.9,
            'photo_url' => 'https://res.cloudinary.com/test/store.jpg',
        ]);
        Sanctum::actingAs($sales);
        $this->postJson('/api/v1/visit-reports', [
            'store_type' => 'field',
            'store_id' => $fieldStore->id,
            'notes' => 'Laporan kunjungan lapangan',
            'pic_name' => 'Ibu Sari',
            'activities' => ['Pengecekan toko'],
            'stock_percentage' => 75,
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.9,
            'sales_accuracy' => 5.2,
            'visited_at' => now()->toDateTimeString(),
        ])->assertCreated()
            ->assertJsonPath('data.store_type', 'field')
            ->assertJsonPath('data.pic_name', 'Ibu Sari');

        $this->assertDatabaseCount('store_visits', 1);
        $this->assertDatabaseCount('visit_reports', 1);
        $report = \App\Models\VisitReport::firstOrFail();
        $storeVisit = StoreVisit::firstOrFail();
        $this->assertSame($storeVisit->id, $report->store_visit_id);
        $this->assertSame('COMPLETED', $storeVisit->status);
        $this->assertNull($storeVisit->odoo_partner_id);
        $this->assertNull($storeVisit->active_store_key);
        $this->assertNull($storeVisit->active_sales_key);

        $this->mock(OdooService::class, function ($mock): void {
            $mock->shouldNotReceive('getCompleteStoreData');
        });
        $this->mock(OdooClient::class, function ($mock): void {
            $mock->shouldNotReceive('executeKw');
        });

        $this->getJson('/api/store-visits/history')
            ->assertOk()
            ->assertJsonPath('data.0.store_type', 'field')
            ->assertJsonPath('data.0.partner.name', 'Toko Lapangan Sukses')
            ->assertJsonPath('data.0.report.store_visit_id', $storeVisit->id);

        $this->getJson('/api/visits/active')
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->actingAs($sales, 'web')
            ->get('/visit-reports')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Visit_Reports/main_visitreport')
                ->where('visits.total', 1)
                ->where('visits.data.0.store_type', 'field')
                ->where('visits.data.0.partner.name', 'Toko Lapangan Sukses')
                ->where('visits.data.0.report.notes', 'Laporan kunjungan lapangan')
                ->where('visits.data.0.report.pic_name', 'Ibu Sari')
                ->where('visits.data.0.report.activities.0', 'Pengecekan toko')
                ->where('visits.data.0.sales.name', $sales->name));
    }

    public function test_field_report_uses_visited_at_as_one_wib_time_without_a_duration(): void
    {
        $sales = User::factory()->create(['status' => 'active']);
        $fieldStore = FieldStore::create([
            'user_id' => $sales->id,
            'store_name' => 'Toko Baru Bertimestamp',
            'address' => 'Jl. Contoh No. 10',
            'latitude' => -6.2,
            'longitude' => 106.9,
            'photo_url' => 'https://res.cloudinary.com/test/store.jpg',
        ]);
        $fieldStore->forceFill([
            'created_at' => '2026-11-15 09:00:00',
        ])->save();
        Sanctum::actingAs($sales);

        $this->postJson('/api/v1/visit-reports', [
            'store_type' => 'field',
            'store_id' => $fieldStore->id,
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.9,
            'visited_at' => '2026-11-15T15:44:55Z',
        ])->assertCreated();

        $this->mock(OdooService::class, function ($mock): void {
            $mock->shouldNotReceive('getCompleteStoreData');
        });

        $this->actingAs($sales, 'web')
            ->get('/visit-reports?quick_filter=all')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('visits.data.0.store_type', 'field')
                ->where('visits.data.0.visited_at', '22:44:55')
                ->where('visits.data.0.check_in_at', null)
                ->where('visits.data.0.check_out_at', null)
                ->where('visits.data.0.duration_seconds', null)
                ->where('statistics.average_duration', '0m 00s'));
    }

    public function test_regular_visit_report_keeps_its_check_in_check_out_and_duration(): void
    {
        $sales = User::factory()->create(['status' => 'active']);
        $partnerId = 987654;
        StoreVisit::create([
            'store_type' => 'regular',
            'field_store_id' => null,
            'odoo_partner_id' => $partnerId,
            'sales_id' => $sales->id,
            'visit_date' => '2026-11-15',
            'status' => 'COMPLETED',
            'active_store_key' => null,
            'active_sales_key' => null,
            'check_in_at' => '2026-11-15 15:44:48',
            'check_out_at' => '2026-11-15 22:44:55',
        ]);
        $this->mock(OdooService::class, function ($mock) use ($partnerId): void {
            $mock->shouldReceive('getCompleteStoreData')
                ->twice()
                ->with([$partnerId])
                ->andReturn([]);
        });

        $this->actingAs($sales, 'web')
            ->get('/visit-reports?quick_filter=all')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('visits.data.0.store_type', 'regular')
                ->where('visits.data.0.check_in_at', '15:44:48')
                ->where('visits.data.0.check_out_at', '22:44:55')
                ->where('visits.data.0.duration_seconds', 25207)
                ->where('statistics.average_duration', '420m 07s'));
    }
}
