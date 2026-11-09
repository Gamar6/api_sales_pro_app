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
}
