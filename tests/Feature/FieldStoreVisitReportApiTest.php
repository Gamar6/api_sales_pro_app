<?php

namespace Tests\Feature;

use App\Models\FieldStore;
use App\Models\StoreModel;
use App\Models\StoreVisit;
use App\Models\User;
use App\Models\VisitReport;
use App\Services\Odoo\OdooClient;
use App\Services\OdooService;
use App\Services\CloudinaryService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FieldStoreVisitReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_can_create_a_field_store_with_a_cloudinary_photo(): void
    {
        $sales = User::factory()->create(['status' => 'active']);
        Sanctum::actingAs($sales);
        $this->mock(OdooService::class, function ($mock): void {
            $mock->shouldNotReceive('execute_kw');
        });

        $this->mock(CloudinaryService::class, function ($mock): void {
            $mock->shouldReceive('uploadImage')
                ->once()
                ->withArgs(function (UploadedFile $photo, string $folder): bool {
                    return $photo->getClientOriginalName() === 'store.jpg'
                        && str_starts_with($folder, 'app_sales/field_stores/');
                })
                ->andReturn([
                    'url' => 'https://res.cloudinary.com/test/store.jpg',
                    'public_id' => 'test/store',
                ]);
        });

        $this->post('/api/v1/field-stores', [
            'store_name' => 'Toko Lapangan',
            'owner_name' => 'Budi',
            'phone_number' => '08123456789',
            'address' => 'Jl. Merdeka No. 1',
            'latitude' => '-6.20000000',
            'longitude' => '106.81666667',
            'photo' => UploadedFile::fake()->image('store.jpg'),
        ])->assertCreated()
            ->assertJsonPath('data.user_id', $sales->id)
            ->assertJsonPath('data.store_name', 'Toko Lapangan')
            ->assertJsonPath('data.owner_name', 'Budi')
            ->assertJsonPath('data.phone_number', '08123456789')
            ->assertJsonPath('data.address', 'Jl. Merdeka No. 1')
            ->assertJsonPath('data.latitude', -6.2)
            ->assertJsonPath('data.longitude', 106.81666667)
            ->assertJsonPath('data.photo_url', 'https://res.cloudinary.com/test/store.jpg')
            ->assertJsonStructure(['data' => ['id', 'created_at']]);

        $this->assertDatabaseHas('field_stores', [
            'user_id' => $sales->id,
            'store_name' => 'Toko Lapangan',
            'latitude' => -6.2,
            'longitude' => 106.81666667,
        ]);
    }

    public function test_sales_can_create_a_visit_report_for_a_field_store(): void
    {
        $sales = User::factory()->create(['status' => 'active']);
        $fieldStore = FieldStore::create([
            'user_id' => $sales->id,
            'store_name' => 'Toko Baru',
            'address' => 'Jl. Merdeka No. 1',
            'latitude' => -6.2,
            'longitude' => 106.81666667,
            'photo_url' => 'https://res.cloudinary.com/test/store.jpg',
        ]);
        Sanctum::actingAs($sales);

        $this->mock(CloudinaryService::class, function ($mock): void {
            $mock->shouldReceive('uploadImage')
                ->once()
                ->andReturn(['url' => 'https://res.cloudinary.com/test/check-in.jpg']);
        });

        $this->post('/api/v1/visit-reports', [
            'store_type' => 'field',
            'store_id' => $fieldStore->id,
            'pic_name' => 'Budi',
            'activities' => ['Pengecekan toko'],
            'stock_percentage' => 80,
            'notes' => 'Kunjungan pertama',
            'check_in_photo' => UploadedFile::fake()->image('check-in.jpg'),
            'check_in_lat' => '-6.20000000',
            'check_in_lng' => '106.81666667',
            'sales_accuracy' => 4.5,
            'location_captured_at' => '2026-11-06 09:00:00',
            'visited_at' => '2026-11-06 09:00:00',
        ])->assertCreated()
            ->assertJsonPath('data.user_id', $sales->id)
            ->assertJsonPath('data.store_type', 'field')
            ->assertJsonPath('data.store_id', $fieldStore->id)
            ->assertJsonPath('data.pic_name', 'Budi')
            ->assertJsonPath('data.activities.0', 'Pengecekan toko')
            ->assertJsonPath('data.check_in_photo', 'https://res.cloudinary.com/test/check-in.jpg');

        $this->assertDatabaseHas('visit_reports', [
            'user_id' => $sales->id,
            'store_type' => 'field',
            'store_id' => $fieldStore->id,
            'notes' => 'Kunjungan pertama',
            'sales_accuracy' => 4.5,
            'distance_from_store' => 0,
            'is_outside_radius' => false,
        ]);
        $report = VisitReport::query()->where('store_type', 'field')->firstOrFail();
        $this->assertSame(
            ['https://res.cloudinary.com/test/check-in.jpg'],
            $report->photos
        );
        $storeVisit = StoreVisit::findOrFail($report->store_visit_id);
        $this->assertSame('COMPLETED', $storeVisit->status);
        $this->assertNotNull($storeVisit->check_out_at);
    }

    public function test_multipart_photos_array_is_uploaded_and_used_as_check_in_photo(): void
    {
        $sales = User::factory()->create(['status' => 'active']);
        $fieldStore = FieldStore::create([
            'user_id' => $sales->id,
            'store_name' => 'Toko Foto Array',
            'address' => 'Jl. Merdeka No. 1',
            'latitude' => -6.2,
            'longitude' => 106.81666667,
            'photo_url' => 'https://res.cloudinary.com/test/store.jpg',
        ]);
        Sanctum::actingAs($sales);

        $this->mock(CloudinaryService::class, function ($mock): void {
            $mock->shouldReceive('uploadImage')
                ->once()
                ->withArgs(function (UploadedFile $photo, string $folder): bool {
                    return $photo->getClientOriginalName() === 'report.jpg'
                        && str_starts_with($folder, 'app_sales/visit_reports/');
                })
                ->andReturn([
                    'url' => 'https://res.cloudinary.com/test/visit-report.jpg',
                    'public_id' => 'test/visit-report',
                ]);
        });

        $this->post('/api/v1/visit-reports', [
            'store_type' => 'field',
            'store_id' => $fieldStore->id,
            'photos' => [UploadedFile::fake()->image('report.jpg')],
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.81666667,
            'visited_at' => '2026-11-06 09:00:00',
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath(
                'data.check_in_photo',
                'https://res.cloudinary.com/test/visit-report.jpg'
            );

        $report = VisitReport::query()->where('store_type', 'field')->firstOrFail();
        $this->assertSame(
            ['https://res.cloudinary.com/test/visit-report.jpg'],
            $report->photos
        );
        $this->assertSame(
            $report->photos[0],
            $report->check_in_photo
        );
    }

    public function test_sales_can_create_a_visit_report_for_a_regular_store(): void
    {
        $sales = User::factory()->create(['status' => 'active']);
        $store = StoreModel::create([
            'odoo_id' => 501,
            'name' => 'Toko Terdaftar',
        ]);
        Sanctum::actingAs($sales);

        $this->postJson('/api/v1/visit-reports', [
            'store_type' => 'regular',
            'store_id' => $store->odoo_id,
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.81666667,
            'visited_at' => '2026-11-06 09:00:00',
        ])->assertCreated()
            ->assertJsonPath('data.store_type', 'regular')
            ->assertJsonPath('data.store_id', $store->odoo_id)
            ->assertJsonPath('data.check_in_photo', null);

        $this->assertDatabaseHas('visit_reports', [
            'user_id' => $sales->id,
            'store_type' => 'regular',
            'store_id' => $store->odoo_id,
        ]);
    }

    public function test_visit_report_rejects_a_store_id_of_the_wrong_type(): void
    {
        $sales = User::factory()->create(['status' => 'active']);
        $fieldStore = FieldStore::create([
            'user_id' => $sales->id,
            'store_name' => 'Toko Baru',
            'address' => 'Jl. Merdeka No. 1',
            'latitude' => -6.2,
            'longitude' => 106.81666667,
            'photo_url' => 'https://res.cloudinary.com/test/store.jpg',
        ]);
        Sanctum::actingAs($sales);

        $this->postJson('/api/v1/visit-reports', [
            'store_type' => 'regular',
            'store_id' => $fieldStore->id,
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.81666667,
            'visited_at' => '2026-11-06 09:00:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('store_id');

        $this->assertSame(0, VisitReport::count());
    }

    public function test_field_store_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/v1/field-stores', [])
            ->assertUnauthorized();
    }

    public function test_visit_report_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/v1/visit-reports', [])
            ->assertUnauthorized();
    }

    public function test_field_store_validation_returns_laravel_errors_and_does_not_upload(): void
    {
        Sanctum::actingAs(User::factory()->create(['status' => 'active']));
        $this->mock(CloudinaryService::class, function ($mock): void {
            $mock->shouldNotReceive('uploadImage');
        });

        $this->postJson('/api/v1/field-stores', [
            'store_name' => '',
            'latitude' => 100,
            'longitude' => 181,
        ])->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['store_name', 'address', 'latitude', 'longitude', 'photo']]);

        $this->assertDatabaseCount('field_stores', 0);
    }

    public function test_field_store_cloudinary_failure_does_not_create_a_record(): void
    {
        Sanctum::actingAs(User::factory()->create(['status' => 'active']));
        $this->mock(CloudinaryService::class, function ($mock): void {
            $mock->shouldReceive('uploadImage')->once()->andThrow(new \RuntimeException('Cloudinary unavailable'));
        });

        $this->post('/api/v1/field-stores', [
            'store_name' => 'Toko Lapangan',
            'address' => 'Jl. Merdeka No. 1',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'photo' => UploadedFile::fake()->image('store.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(502)
            ->assertJsonPath('message', 'Foto toko gagal diunggah. Silakan coba kembali.');

        $this->assertDatabaseCount('field_stores', 0);
    }

    public function test_user_cannot_create_visit_report_for_another_users_field_store(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $otherSales = User::factory()->create(['status' => 'active']);
        $fieldStore = FieldStore::create([
            'user_id' => $owner->id,
            'store_name' => 'Toko Milik Sales Lain',
            'address' => 'Jl. Merdeka No. 1',
            'latitude' => -6.2,
            'longitude' => 106.81666667,
            'photo_url' => 'https://res.cloudinary.com/test/store.jpg',
        ]);
        Sanctum::actingAs($otherSales);

        $this->postJson('/api/v1/visit-reports', [
            'store_type' => 'field',
            'store_id' => $fieldStore->id,
            'check_in_lat' => -6.2,
            'check_in_lng' => 106.8,
            'visited_at' => '2026-11-06 09:00:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('store_id');

        $this->assertDatabaseCount('visit_reports', 0);
    }

    public function test_visit_report_can_be_replayed_with_an_idempotency_key_without_duplicate_upload_or_row(): void
    {
        $sales = User::factory()->create(['status' => 'active']);
        $fieldStore = FieldStore::create([
            'user_id' => $sales->id,
            'store_name' => 'Toko Baru',
            'address' => 'Jl. Merdeka No. 1',
            'latitude' => -6.2,
            'longitude' => 106.81666667,
            'photo_url' => 'https://res.cloudinary.com/test/store.jpg',
        ]);
        Sanctum::actingAs($sales);

        $photo = UploadedFile::fake()->image('check-in.jpg');
        $this->mock(CloudinaryService::class, function ($mock): void {
            $mock->shouldReceive('uploadImage')
                ->once()
                ->andReturn([
                    'url' => 'https://res.cloudinary.com/test/check-in.jpg',
                    'public_id' => 'test/check-in',
                ]);
        });

        $payload = [
            'store_type' => 'field',
            'store_id' => $fieldStore->id,
            'notes' => 'Kunjungan pertama',
            'check_in_photo' => $photo,
            'check_in_lat' => '-6.20000000',
            'check_in_lng' => '106.81666667',
            'visited_at' => '2026-11-06 09:00:00',
        ];

        $this->post('/api/v1/visit-reports', $payload, [
            'Accept' => 'application/json',
            'Idempotency-Key' => 'visit-report-unique-001',
        ])->assertCreated();
        $storeVisitId = VisitReport::query()->value('store_visit_id');
        $this->assertNotNull($storeVisitId);

        $this->post('/api/v1/visit-reports', $payload, [
            'Accept' => 'application/json',
            'Idempotency-Key' => 'visit-report-unique-001',
        ])->assertCreated()
            ->assertHeader('Idempotent-Replayed', 'true');

        $payload['notes'] = 'Payload berbeda';
        $this->post('/api/v1/visit-reports', $payload, [
            'Accept' => 'application/json',
            'Idempotency-Key' => 'visit-report-unique-001',
        ])->assertStatus(409);

        $this->assertDatabaseCount('visit_reports', 1);
        $this->assertDatabaseCount('store_visits', 1);
        $this->assertSame(
            $storeVisitId,
            StoreVisit::query()->value('id')
        );
    }

    public function test_regular_store_visit_report_still_completes_the_existing_visit(): void
    {
        $sales = User::factory()->create(['status' => 'active']);
        $visit = StoreVisit::create([
            'store_type' => 'regular',
            'field_store_id' => null,
            'odoo_partner_id' => 501,
            'sales_id' => $sales->id,
            'visit_date' => now()->toDateString(),
            'status' => 'IN_VISIT',
            'active_store_key' => 501,
            'active_sales_key' => $sales->id,
            'check_in_at' => now()->subMinutes(10),
        ]);
        Sanctum::actingAs($sales);

        $this->mock(OdooClient::class);
        $this->mock(OdooService::class, function ($mock): void {
            $mock->shouldReceive('execute_kw')
                ->once()
                ->andReturn([[
                    'id' => 501,
                    'partner_latitude' => -6.2,
                    'partner_longitude' => 106.9,
                ]]);
        });

        Cloudinary::shouldReceive('upload')
            ->once()
            ->andReturnSelf();
        Cloudinary::shouldReceive('getSecurePath')
            ->once()
            ->andReturn('https://res.cloudinary.com/test/regular-report.jpg');

        $this->post("/api/store-visits/{$visit->id}/submit-report", [
            'pic_name' => 'PIC Reguler',
            'activities' => ['Pengecekan stok'],
            'stock_percentage' => 80,
            'notes' => 'Catatan tetap terpisah',
            'photos' => [UploadedFile::fake()->image('report.jpg')],
            'sales_latitude' => -6.2,
            'sales_longitude' => 106.9,
            'sales_accuracy' => 4.5,
            'location_captured_at' => now()->toDateTimeString(),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.store_visit_id', $visit->id);

        $this->assertDatabaseHas('store_visits', [
            'id' => $visit->id,
            'status' => 'COMPLETED',
            'active_store_key' => null,
            'active_sales_key' => null,
        ]);

        $report = VisitReport::query()->where('store_visit_id', $visit->id)->firstOrFail();
        $this->assertSame('PIC Reguler', $report->pic_name);
        $this->assertSame(['Pengecekan stok'], $report->activities);
        $this->assertSame(80, $report->stock_percentage);
        $this->assertSame('Catatan tetap terpisah', $report->notes);
        $this->assertSame(
            ['https://res.cloudinary.com/test/regular-report.jpg'],
            $report->photos
        );
    }
}
