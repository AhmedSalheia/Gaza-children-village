<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Admin\Inventory\InventoryDashboard;
use App\Livewire\Admin\Inventory\Items\ItemIndex;
use App\Livewire\Admin\Inventory\Items\ItemForm;
use App\Livewire\Admin\Inventory\Warehouses\WarehouseIndex;
use App\Livewire\Admin\Inventory\Stock\StockOverview;
use App\Livewire\Admin\Inventory\Movements\MovementIndex;
use App\Livewire\Admin\Inventory\Requests\RequestIndex;
use App\Livewire\Admin\Inventory\Requests\RequestDetail;
use App\Livewire\Admin\Inventory\Approvals\ApprovalInbox;
use App\Livewire\Admin\Inventory\Reallocation\ReallocationMap;
use App\Livewire\Admin\Inventory\Counts\InventoryCount;
use App\Livewire\Admin\Inventory\Reports\InventoryReports;

Route::middleware(['web'])
    ->prefix('admin/inventory')
    ->name('admin.inventory.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/', InventoryDashboard::class)
            ->name('index');


        /*
        |--------------------------------------------------------------------------
        | Items
        |--------------------------------------------------------------------------
        */

        Route::get('/items', ItemIndex::class)
            ->name('items.index');

        Route::get('/items/create', ItemForm::class)
            ->name('items.create');

        Route::get('/items/{item}/edit', ItemForm::class)
            ->name('items.edit');


        /*
        |--------------------------------------------------------------------------
        | Warehouses
        |--------------------------------------------------------------------------
        */

        Route::get('/warehouses', WarehouseIndex::class)
            ->name('warehouses.index');


        /*
        |--------------------------------------------------------------------------
        | Stock
        |--------------------------------------------------------------------------
        */

        Route::get('/stock', StockOverview::class)
            ->name('stock.index');


        /*
        |--------------------------------------------------------------------------
        | Movements
        |--------------------------------------------------------------------------
        */

        Route::get('/movements', MovementIndex::class)
            ->name('movements.index');


        /*
        |--------------------------------------------------------------------------
        | Requests
        |--------------------------------------------------------------------------
        */

        Route::get('/requests', RequestIndex::class)
            ->name('requests.index');

        Route::get('/requests/{request}', RequestDetail::class)
            ->name('requests.show');


        /*
        |--------------------------------------------------------------------------
        | Approvals
        |--------------------------------------------------------------------------
        */

        Route::get('/approvals', ApprovalInbox::class)
            ->name('approvals.index');


        /*
        |--------------------------------------------------------------------------
        | Reallocation
        |--------------------------------------------------------------------------
        */

        Route::get('/reallocation', ReallocationMap::class)
            ->name('reallocation.index');


        /*
        |--------------------------------------------------------------------------
        | Inventory Counts
        |--------------------------------------------------------------------------
        */

        Route::get('/counts', InventoryCount::class)
            ->name('counts.index');


        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */

        Route::get('/reports', InventoryReports::class)
            ->name('reports.index');
    });
