<?php

namespace Tests\Unit;

use App\Http\Controllers\StockController;
use App\Services\Odoo\OdooProductService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class StockControllerTest extends TestCase
{
    public function test_stock_titles_do_not_include_the_finished_product_prefix(): void
    {
        $odoo = Mockery::mock(OdooProductService::class);
        $odoo->shouldReceive('getSoldProductsWithStock')
            ->once()
            ->with(1)
            ->andReturn([
                ['title' => '[PRODUK JADI] Beef', 'raw_qty' => 12],
                ['title' => 'Chicken', 'raw_qty' => 8],
            ]);

        $response = (new StockController($odoo))
            ->index(Request::create('/api/stocks', 'GET'));

        $data = $response->getData(true)['data'];

        $this->assertSame('Beef', $data[0]['title']);
        $this->assertSame('Chicken', $data[1]['title']);
    }
}
