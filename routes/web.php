<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/',[\App\Http\Controllers\StockTransferController::class,'home'])->name('home');
Route::get('/stock-transfer-list',[\App\Http\Controllers\StockTransferController::class,'transfer_list'])->name('stock_transfer_list');
Route::get('/stock-transfer-print/{id}',[\App\Http\Controllers\StockTransferController::class,'transfer_print'])->name('stock_transfer_print');
Route::get('/stock-transfer',[\App\Http\Controllers\StockTransferController::class,'transfer'])->name('stock_transfer');
Route::get('/stock-transfer/{id}/edit',[\App\Http\Controllers\StockTransferController::class,'transfer_edit'])->name('transfer_edit');
Route::post('/stock-transfer/{id}/update',[\App\Http\Controllers\StockTransferController::class,'stock_transfer_update'])->name('stock_transfer_update');
Route::post('/stock-transfer-store',[\App\Http\Controllers\StockTransferController::class,'transfer_store'])->name('stock_transfer_store');
Route::get('/get-products',[\App\Http\Controllers\StockTransferController::class,'get_products'])->name('get_products');
Route::get('/get-product-size',[\App\Http\Controllers\StockTransferController::class,'get_product_size'])->name('get_product_size');
Route::get('/get-product-unit',[\App\Http\Controllers\StockTransferController::class,'get_product_unit'])->name('get_product_unit');
Route::get('/get-product-stock',[\App\Http\Controllers\StockTransferController::class,'get_product_stock'])->name('get_product_stock');

Route::get('/check-database-connection', function () {
    try {
        DB::connection()->getPdo();
        return "Database connection is okay.";
    } catch (\Exception $e) {
        return "Could not connect to the database. Error: " . $e->getMessage();
    }
});
