<?php

namespace Tests\Feature;

use App\Models\StoreVisit;
use App\Models\User;
use App\Services\Odoo\OdooClient;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreVisitWeeklyRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-09-03 10:00:00', 'Asia/Jakarta')
        );

        $this->mock(OdooClient::class, function ($mock) {
            $mock->shouldReceive('executeKw')->andReturn([]);
        });
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_sales_can_only_have_one_active_visit(): void
    {
        $sales = User::factory()->create();
        Sanctum::actingAs($sales);

        $this->postJson('/api/store-visits/claim', ['odoo_partner_id' => 101])
            ->assertCreated();

        $this->postJson('/api/store-visits/claim', ['odoo_partner_id' => 102])
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Selesaikan atau batalkan kunjungan aktif sebelum mengunjungi toko lain.'
            );
    }

    public function test_other_sales_cannot_claim_an_active_store(): void
    {
        $firstSales = User::factory()->create();
        $secondSales = User::factory()->create();

        Sanctum::actingAs($firstSales);
        $this->postJson('/api/store-visits/claim', ['odoo_partner_id' => 201])
            ->assertCreated();

        Sanctum::actingAs($secondSales);
        $this->postJson('/api/store-visits/claim', ['odoo_partner_id' => 201])
            ->assertStatus(422)
            ->assertJsonPath('message', "Toko ini sedang dikunjungi oleh {$firstSales->name}.");
    }

    public function test_completed_store_is_locked_until_next_monday(): void
    {
        $firstSales = User::factory()->create();
        $secondSales = User::factory()->create();

        $this->createVisit($firstSales, 301, 'COMPLETED', '2026-09-01');

        Sanctum::actingAs($secondSales);
        $this->postJson('/api/store-visits/claim', ['odoo_partner_id' => 301])
            ->assertStatus(422)
            ->assertJsonPath('message', "Toko ini sudah dikunjungi minggu ini oleh {$firstSales->name}.");
    }

    public function test_store_completed_before_monday_is_available_again(): void
    {
        $firstSales = User::factory()->create();
        $secondSales = User::factory()->create();

        $this->createVisit($firstSales, 401, 'COMPLETED', '2026-08-30');

        Sanctum::actingAs($secondSales);
        $this->postJson('/api/store-visits/claim', ['odoo_partner_id' => 401])
            ->assertCreated();
    }

    public function test_active_visit_from_previous_week_still_locks_store(): void
    {
        $firstSales = User::factory()->create();
        $secondSales = User::factory()->create();

        $this->createVisit($firstSales, 501, 'IN_VISIT', '2026-08-28');

        Sanctum::actingAs($secondSales);
        $this->postJson('/api/store-visits/claim', ['odoo_partner_id' => 501])
            ->assertStatus(422)
            ->assertJsonPath('message', "Toko ini sedang dikunjungi oleh {$firstSales->name}.");
    }

    public function test_visit_started_last_week_and_completed_this_week_stays_locked(): void
    {
        $firstSales = User::factory()->create();
        $secondSales = User::factory()->create();

        StoreVisit::create([
            'odoo_partner_id' => 551,
            'sales_id' => $firstSales->id,
            'visit_date' => '2026-08-30',
            'status' => 'COMPLETED',
            'check_in_at' => CarbonImmutable::parse(
                '2026-08-30 23:30:00',
                'Asia/Jakarta'
            ),
            'check_out_at' => CarbonImmutable::parse(
                '2026-08-31 00:15:00',
                'Asia/Jakarta'
            ),
        ]);

        Sanctum::actingAs($secondSales);
        $this->postJson('/api/store-visits/claim', ['odoo_partner_id' => 551])
            ->assertStatus(422)
            ->assertJsonPath('message', "Toko ini sudah dikunjungi minggu ini oleh {$firstSales->name}.");
    }

    public function test_cancelling_visit_releases_sales_and_store(): void
    {
        $firstSales = User::factory()->create();
        $secondSales = User::factory()->create();

        Sanctum::actingAs($firstSales);
        $visitId = $this->postJson('/api/store-visits/claim', ['odoo_partner_id' => 601])
            ->assertCreated()
            ->json('data.store_visit_id');

        $this->postJson("/api/store-visits/{$visitId}/cancel")
            ->assertOk();

        Sanctum::actingAs($secondSales);
        $this->postJson('/api/store-visits/claim', ['odoo_partner_id' => 601])
            ->assertCreated();
    }

    private function createVisit(
        User $sales,
        int $partnerId,
        string $status,
        string $visitDate
    ): StoreVisit {
        return StoreVisit::create([
            'odoo_partner_id' => $partnerId,
            'sales_id' => $sales->id,
            'visit_date' => $visitDate,
            'status' => $status,
            'active_store_key' => $status === 'IN_VISIT' ? $partnerId : null,
            'active_sales_key' => $status === 'IN_VISIT' ? $sales->id : null,
            'check_in_at' => CarbonImmutable::parse($visitDate, 'Asia/Jakarta')->setHour(10),
            'check_out_at' => $status === 'COMPLETED'
                ? CarbonImmutable::parse($visitDate, 'Asia/Jakarta')->setHour(11)
                : null,
        ]);
    }
}