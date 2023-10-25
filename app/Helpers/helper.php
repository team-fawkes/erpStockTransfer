<?php

use Illuminate\Support\Facades\DB;

function getProductName($id)
{
    $product = DB::table('product_info')->where('id',$id)
        ->first();
    return $product->product_name??'--';
}
function getProductInfo($id)
{
    $product = DB::table('product_info')->where('id',$id)
        ->first();
    return $product;
}
function getProductGrade($id)
{
    $product = DB::table('product_info')->where('id',$id)->first();
    $grade = DB::table('product_grade')->where('id',$product->product_grade_id)->first();
    return $grade->grade??'--';
}
function getProductSize($id)
{
    $product = DB::table('product_info')->where('id',$id)->first();
    $grade = DB::table('product_model')->where('id',$product->product_model_id)->first();
    return $grade->size_model??'--';
}
function getProductUnit($id)
{
    $product = DB::table('product_info')->where('id',$id)->first();
    $grade = DB::table('product_unit')->where('id',$product->product_unit_id)->first();
    return $grade->unit_name??'--';
}

function getProductUPrice($id)
{
    $product = DB::table('product_info')->where('id',$id)->first();
    return $product->purchase_price??0;
}
function getProductStock($id,$store)
{
    $stock = DB::table('stock_transaction')
        ->where('product_info_id', $id)
        ->where('store_info_id', $store)
        ->sum('purchase_quantity');
    return $stock;
}
function getTotalTransferPrice($id){
    $transfer = DB::table('stock_transfer')->where('id',$id)->first();
    $stock_transfer_details = DB::table('stock_transfer_details')->where('stock_transfer_id',$transfer->id)->get();
    $total = 0;
    foreach ($stock_transfer_details as $std){
        $total += round($std->quantity * getProductUPrice($std->product_info_id),2);

    }
    return $total;
}
