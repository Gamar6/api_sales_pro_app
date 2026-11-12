<?php

namespace Tests\Feature;

use App\Models\StoreVisit;
use App\Models\User;
use App\Models\VisitCorrection;
use App\Models\VisitReport;
use App\Services\Odoo\OdooClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VisitCorrectionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_correct_a_completed_visit_at_the_deadline_and_original_report_is_unchanged(): void
    {
        $sales = User::factory()->create(['status' => 'active']);
        $visit = $this->completedVisit($sales);
        $original = $visit->report;
        Carbon::setTestNow($visit->check_out_at->copy()->addHours(12));
        Sanctum::actingAs($sales);

        $this->postJson("/api/store-visits/{$visit->id}/corrections", [
            'stock_percentage' => 60,
            'reason' => 'Stok awal salah dihitung.',
        ], ['Idempotency-Key' => 'correction-deadline-001'])
            ->assertCreated()
            ->assertJsonPath('data.correction.status', 'approved')
            ->assertJsonPath('data.effective_data.stock_percentage', 60);

        $this->assertDatabaseHas('visit_reports', [
            'id' => $original->id,
            'stock_percentage' => 80,
            'notes' => 'Laporan awal',
        ]);
        $this->assertDatabaseHas('visit_corrections', [
            'store_visit_id' => $visit->id,
            'user_id' => $sales->id,
            'status' => 'approved',
            'reason' => 'Stok awal salah dihitung.',
        ]);

        Carbon::setTestNow();
    }

    public function test_correction_requires_owner_completed_status_and_active_deadline(): void
    {
        $owner = User::factory()->create(['status' => 'active']);
        $otherSales = User::factory()->create(['status' => 'active']);
        $visit = $this->completedVisit($owner);
        Sanctum::actingAs($otherSales);

        $this->postJson("/api/store-visits/{$visit->id}/corrections", [
            'notes' => 'Alterado',
            'reason' => 'Correção solicitada.',
        ], ['Idempotency-Key' => 'correction-not-owner'])
            ->assertNotFound();
        $this->getJson("/api/store-visits/{$visit->id}/corrections")
            ->assertNotFound();

        Sanctum::actingAs($owner);
        Carbon::setTestNow($visit->check_out_at->copy()->addHours(12)->addSecond());
        $this->postJson("/api/store-visits/{$visit->id}/corrections", [
            'notes' => 'Alterado',
            'reason' => 'Correção solicitada.',
        ], ['Idempotency-Key' => 'correction-too-late'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('visit');

        $activeVisit = StoreVisit::create([
            'store_type' => 'regular',
            'field_store_id' => null,
            'odoo_partner_id' => 502,
            'sales_id' => $owner->id,
            'visit_date' => now()->toDateString(),
            'status' => 'IN_VISIT',
            'active_store_key' => null,
            'active_sales_key' => null,
            'check_in_at' => now()->subMinutes(10),
            'check_out_at' => null,
        ]);
        $this->postJson("/api/store-visits/{$activeVisit->id}/corrections", [
            'notes' => 'Alterado',
            'reason' => 'Correção solicitada.',
        ], ['Idempotency-Key' => 'correction-active-visit'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('visit');

        $this->assertDatabaseCount('visit_corrections', 0);
        Carbon::setTestNow();
    }

    public function test_multiple_corrections_merge_effective_values_and_idempotent_retries_do_not_duplicate(): void
    {
        $sales = User::factory()->create(['status' => 'active']);
        $visit = $this->completedVisit($sales);
        Sanctum::actingAs($sales);

        $firstPayload = [
            'stock_percentage' => 70,
            'reason' => 'Persentase stok dikoreksi.',
        ];
        $this->postJson("/api/store-visits/{$visit->id}/corrections", $firstPayload, [
            'Idempotency-Key' => 'correction-multiple-001',
        ])->assertCreated();

        $this->postJson("/api/store-visits/{$visit->id}/corrections", $firstPayload, [
            'Idempotency-Key' => 'correction-multiple-001',
        ])->assertCreated()
            ->assertHeader('Idempotent-Replayed', 'true');

        $this->postJson("/api/store-visits/{$visit->id}/corrections", [
            'stock_pcs' => 45,
            'reason' => 'Jumlah stok dikoreksi.',
        ], ['Idempotency-Key' => 'correction-multiple-002'])
            ->assertCreated()
            ->assertJsonPath('data.effective_data.stock_percentage', 70)
            ->assertJsonPath('data.effective_data.stock_pcs', 45);

        $this->postJson("/api/store-visits/{$visit->id}/corrections", [
            'stock_percentage' => 65,
            'reason' => 'Payload key reuse.',
        ], ['Idempotency-Key' => 'correction-multiple-001'])
            ->assertStatus(409);

        $this->getJson("/api/store-visits/{$visit->id}/corrections")
            ->assertOk()
            ->assertJsonPath('data.original_data.stock_percentage', 80)
            ->assertJsonPath('data.effective_data.stock_percentage', 70)
            ->assertJsonPath('data.effective_data.stock_pcs', 45)
            ->assertJsonCount(2, 'data.corrections');

        $this->mock(OdooClient::class, function ($mock): void {
            $mock->shouldReceive('executeKw')
                ->once()
                ->andReturn([['id' => 501, 'name' => 'Toko Uji']]);
        });
        $this->getJson('/api/store-visits/history')
            ->assertOk()
            ->assertJsonPath('data.0.report.stock_percentage', 80)
            ->assertJsonPath('data.0.report_original.stock_percentage', 80)
            ->assertJsonPath('data.0.report_effective.stock_percentage', 70)
            ->assertJsonPath('data.0.report_effective.stock_pcs', 45);

        $this->assertSame(2, VisitCorrection::query()->count());
        $corrections = VisitCorrection::query()->orderBy('id')->get();
        $this->assertSame(80, $corrections[0]->previous_data['stock_percentage']);
        $this->assertSame(70, $corrections[1]->previous_data['stock_percentage']);
        $this->assertDatabaseHas('visit_reports', [
            'id' => $visit->report->id,
            'stock_percentage' => 80,
            'stock_pcs' => 25,
        ]);
    }

    public function test_correction_requires_authentication_and_a_reason_and_idempotency_key(): void
    {
        $sales = User::factory()->create(['status' => 'active']);
        $visit = $this->completedVisit($sales);

        $this->postJson("/api/store-visits/{$visit->id}/corrections", [
            'stock_pcs' => 30,
        ])->assertUnauthorized();

        Sanctum::actingAs($sales);
        $this->postJson("/api/store-visits/{$visit->id}/corrections", [
            'stock_pcs' => 30,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['reason', 'idempotency_key']);
    }

    private function completedVisit(User $sales): StoreVisit
    {
        $completedAt = now()->subHour();
        $visit = StoreVisit::create([
            'store_type' => 'regular',
            'field_store_id' => null,
            'odoo_partner_id' => 501,
            'sales_id' => $sales->id,
            'visit_date' => $completedAt->toDateString(),
            'status' => 'COMPLETED',
            'active_store_key' => null,
            'active_sales_key' => null,
            'check_in_at' => $completedAt->copy()->subMinutes(30),
            'check_out_at' => $completedAt,
        ]);

        VisitReport::create([
            'user_id' => $sales->id,
            'store_type' => 'regular',
            'store_id' => 501,
            'store_visit_id' => $visit->id,
            'pic_name' => 'PIC Awal',
            'activities' => ['Pengecekan stok'],
            'stock_percentage' => 80,
            'stock_pcs' => 25,
            'notes' => 'Laporan awal',
            'photos' => [],
            'visited_at' => $completedAt,
        ]);

        return $visit->load('report');
    }
}
