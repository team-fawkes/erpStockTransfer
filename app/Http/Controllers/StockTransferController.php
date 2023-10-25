<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function home(){
        return view('StockTransfer.home');
    }
    public function transfer_list(){
        $data = array();
        $transfers = DB::table('stock_transfer')
            ->join('store_info as destination', 'stock_transfer.destination_stock_id', '=', 'destination.id')
            ->join('store_info as source', 'stock_transfer.source_stock_id', '=', 'source.id')
            ->leftJoin('stock_transfer_details', 'stock_transfer.id', '=', 'stock_transfer_details.stock_transfer_id')
            ->select(
                'stock_transfer.*',
                'destination.store_name as destination_store_name',
                'source.store_name as source_store_name',
                DB::raw('SUM(stock_transfer_details.quantity) as total_quantity'),
                DB::raw('COUNT(stock_transfer_details.product_info_id) as total_products')
            )
            ->groupBy('stock_transfer.id', 'destination.store_name', 'source.store_name')
            ->get();
        $data['transfers'] =  $transfers;
        return view('StockTransfer.list',$data);
    }
    public function transfer_edit($id){
        $data = array();
        $transfer = DB::table('stock_transfer')->where('id',$id)->first();
        $data['transfer'] = $transfer;
        $data['today'] = date('d-M-Y',strtotime($transfer->transaction_date));
        $data['sources'] = DB::table('store_info')->get();
        $data['suppliers'] = DB::table('supplier_info')->get();
        $data['product_grades'] = DB::table('product_grade')->get();
        $data['source_store'] = DB::table('store_info')->where('id',$transfer->source_stock_id)->first();
        $data['destination_store'] = DB::table('store_info')->where('id',$transfer->destination_stock_id)->first();
        $data['transfer_items'] = DB::table('stock_transfer_details')->where('stock_transfer_id',$transfer->id)->get();
        return view('StockTransfer.edit',$data);
    }
    public function stock_transfer_update(Request $request,$id){
        $request->validate([
            'from_store_id' => 'required',
            'date' => 'required',
            'to_store_id' => 'required',
            'suppliers' => 'required',
            'product_name' => 'required',
            'quantity' => 'required',
            'rate' => 'required',
        ]);

        DB::table('stock_transfer_details')->where('stock_transfer_id',$id)->delete();
        DB::table('stock_transaction')->where('stock_transfer_id',$id)->delete();

        $dateString = $request->date;
        $carbonDate = Carbon::createFromFormat('d-M-Y', $dateString);
        $formattedDate = $carbonDate->format('Y-m-d H:i:s');
        $currentTime = Carbon::now();
        $currentTimeFormatted = $currentTime->format('Y-m-d H:i:s');
        $stk_tr_id = $id;
        $stock_transfer_data = [
            'id' => $stk_tr_id,
            'version' => 0,
            'branch_id' => null,
            'created_by' => 'Desktop PHP',
            'date_created' => $currentTimeFormatted,
            'last_updated' => $currentTimeFormatted,
            'transaction_date' => $formattedDate,
            'source_stock_id' => $request->from_store_id,
            'destination_stock_id' => $request->to_store_id,
        ];
        DB::table('stock_transfer')
            ->where('id', $stk_tr_id) // Assuming 'id' is the primary key
            ->update($stock_transfer_data);

        foreach ($request->product_name as $key=>$value){
            $stock_transfer_details_data = [
                'version' => 0,
                'created_by' => 'Desktop PHP',
                'date_created' => $currentTimeFormatted,
                'last_updated' => $currentTimeFormatted,
                'product_info_id' => $request->product_name[$key],
                'quantity' => $request->quantity[$key],
                'stock_transfer_id' => $stk_tr_id,
            ];
            $st_details = DB::table('stock_transfer_details')->where('product_info_id',$request->product_name[$key])->where('stock_transfer_id',$stk_tr_id)->first();
            if (!$st_details){
                DB::table('stock_transfer_details')->insert($stock_transfer_details_data);
            }
            $inProductData = DB::table('product_info')
                ->where('supplier_info_id', $request->suppliers[$key])
                ->where('product_grade_id', $request->product_grade[$key])
                ->where('store_info_id',$request->to_store_id)
                ->first();
            $transaction = DB::table('stock_transaction')->orderBy('id','desc')->first();
            if ($inProductData){
                DB::table('product_info')
                    ->where('id', $inProductData->id) // Assuming 'id' is the primary key
                    ->update(['purchase_price' => $request->rate[$key]]);
                $stock_transaction_data_in = [
                    'id' => $transaction->id+1,
                    'version' => 0,
                    'created_by' => 'Desktop PHP',
                    'date_created' => $currentTimeFormatted,
                    'last_updated' => $currentTimeFormatted,
                    'product_info_id' => $inProductData->id,
                    'stock_transfer_id' => $stk_tr_id,
                    'transaction_date' => $formattedDate,
                    'store_info_id' => $request->to_store_id,
                    'specification' => 'Stock Transfer to stock',
                    'purchase_quantity' => +$request->quantity[$key],
                ];
            }
            else{
                $p = DB::table('product_info')->orderBy('id','desc')->first();
                $oldProduct = DB::table('product_info')->where('id',$request->product_name[$key])->first();
                $newProduct = [
                    'id' => $p->id+1,
                    'version' => 0,
                    'created_by' => 'Desktop PHP',
                    'date_created' => $currentTimeFormatted,
                    'group_code' => $oldProduct->group_code,
                    'last_updated' => $currentTimeFormatted,
                    'product_entry_date' => $currentTimeFormatted,
                    'product_code' => $oldProduct->product_code,
                    'product_grade_id' => $oldProduct->product_grade_id,
                    'product_group_id' => $oldProduct->product_group_id,
                    'product_model_id' => $oldProduct->product_model_id,
                    'product_name' => $oldProduct->product_name.' (ST)',
                    'product_unit_id' => $oldProduct->product_unit_id,
                    'sale_price' => $oldProduct->sale_price,
                    'purchase_price' => $request->rate[$key],
                    'supplier_info_id' => $oldProduct->supplier_info_id,
                    'store_info_id' => $request->to_store_id,
                ];
                DB::table('product_info')->insert($newProduct);
                $stock_transaction_data_in = [
                    'id' => $transaction->id+1,
                    'version' => 0,
                    'created_by' => 'Desktop PHP',
                    'date_created' => $currentTimeFormatted,
                    'last_updated' => $currentTimeFormatted,
                    'product_info_id' => $p->id+1,
                    'stock_transfer_id' => $stk_tr_id,
                    'transaction_date' => $formattedDate,
                    'store_info_id' => $request->to_store_id,
                    'specification' => 'Stock Transfer to stock',
                    'purchase_quantity' => +$request->quantity[$key],
                ];
            }
            DB::table('stock_transaction')->insert($stock_transaction_data_in);
            $transaction = DB::table('stock_transaction')->orderBy('id','desc')->first();
            $stock_transaction_data_out = [
                'id' => $transaction->id+1,
                'version' => 0,
                'created_by' => 'Desktop PHP',
                'date_created' => $currentTimeFormatted,
                'last_updated' => $currentTimeFormatted,
                'product_info_id' => $request->product_name[$key],
                'stock_transfer_id' => $stk_tr_id,
                'transaction_date' => $formattedDate,
                'store_info_id' => $request->from_store_id,
                'specification' => 'Stock Transfer from stock',
                'purchase_quantity' => -$request->quantity[$key],
            ];
            DB::table('stock_transaction')->insert($stock_transaction_data_out);

        }
        DB::table('last_id_info')
            ->where('id', 34) // Assuming 'id' is the primary key
            ->increment('last_id', 1);
        toastr()->info('Stock Transfer updated successfully!');
        return redirect()->back();
    }
    public function transfer_print($id){
        $data = array();
        $transfer = DB::table('stock_transfer')->where('id',$id)->first();
        $source_store = DB::table('store_info')->where('id',$transfer->source_stock_id)->first();
        $destination_store = DB::table('store_info')->where('id',$transfer->destination_stock_id)->first();
        $stock_transfer_details = DB::table('stock_transfer_details')->where('stock_transfer_id',$transfer->id)->get();

        $data['transfer'] = $transfer;
        $data['source_store'] = $source_store;
        $data['destination_store'] = $destination_store;
        $data['transfer_items'] = $stock_transfer_details;

        $html =  view('StockTransfer.pdf',$data);
        //return $html;
         //Pdf::loadHTML($html)->stream('download.pdf');
        //return $html;

        $pdf = App::make('dompdf.wrapper');
        $pdf->loadHTML($html);
        return $pdf->stream();
    }
    public function transfer(){
        $data = array();
        $data['transfer'] = DB::table('stock_transfer')->orderBy('id','desc')->first();
        $data['today'] = date('d-M-Y',strtotime(now()));
        $data['sources'] = DB::table('store_info')->get();
        $data['suppliers'] = DB::table('supplier_info')->get();
        $data['product_grades'] = DB::table('product_grade')->get();

        return view('StockTransfer.transfer',$data);
    }
    public function transfer_store(Request $request){
        $request->validate([
            'from_store_id' => 'required',
            'date' => 'required',
            'to_store_id' => 'required',
            'suppliers' => 'required',
            'product_name' => 'required',
            'quantity' => 'required',
            'rate' => 'required',
        ]);

        //return $request;

        $stock_tr = DB::table('stock_transfer')->orderBy('id','desc')->first();
        $dateString = $request->date;
        $carbonDate = Carbon::createFromFormat('d-M-Y', $dateString);
        $formattedDate = $carbonDate->format('Y-m-d H:i:s');
        $currentTime = Carbon::now();
        $currentTimeFormatted = $currentTime->format('Y-m-d H:i:s');
        $stk_tr_id = $stock_tr->id+1;
        $stock_transfer_data = [
            'id' => $stk_tr_id,
            'version' => 0,
            'branch_id' => null,
            'created_by' => 'Desktop PHP',
            'date_created' => $currentTimeFormatted,
            'last_updated' => $currentTimeFormatted,
            'transaction_date' => $formattedDate,
            'source_stock_id' => $request->from_store_id,
            'destination_stock_id' => $request->to_store_id,
        ];
         DB::table('stock_transfer')->insert($stock_transfer_data);

        foreach ($request->product_name as $key=>$value){
            $stock_transfer_details_data = [
                'version' => 0,
                'created_by' => 'Desktop PHP',
                'date_created' => $currentTimeFormatted,
                'last_updated' => $currentTimeFormatted,
                'product_info_id' => $request->product_name[$key],
                'quantity' => $request->quantity[$key],
                'stock_transfer_id' => $stk_tr_id,
            ];
            $st_details = DB::table('stock_transfer_details')->where('product_info_id',$request->product_name[$key])->where('stock_transfer_id',$stk_tr_id)->first();
            if (!$st_details){
                DB::table('stock_transfer_details')->insert($stock_transfer_details_data);
            }
            $inProductData = DB::table('product_info')
                ->where('supplier_info_id', $request->suppliers[$key])
                ->where('product_grade_id', $request->product_grade[$key])
                ->where('store_info_id',$request->to_store_id)
                ->first();
            $transaction = DB::table('stock_transaction')->orderBy('id','desc')->first();
            if ($inProductData){
                DB::table('product_info')
                    ->where('id', $inProductData->id) // Assuming 'id' is the primary key
                    ->update(['purchase_price' => $request->rate[$key]]);
                $stock_transaction_data_in = [
                    'id' => $transaction->id+1,
                    'version' => 0,
                    'created_by' => 'Desktop PHP',
                    'date_created' => $currentTimeFormatted,
                    'last_updated' => $currentTimeFormatted,
                    'product_info_id' => $inProductData->id,
                    'stock_transfer_id' => $stk_tr_id,
                    'transaction_date' => $formattedDate,
                    'store_info_id' => $request->to_store_id,
                    'specification' => 'Stock Transfer to stock',
                    'purchase_quantity' => +$request->quantity[$key],
                ];
            }
            else{
                $p = DB::table('product_info')->orderBy('id','desc')->first();
                $oldProduct = DB::table('product_info')->where('id',$request->product_name[$key])->first();
                $newProduct = [
                    'id' => $p->id+1,
                    'version' => 0,
                    'created_by' => 'Desktop PHP',
                    'date_created' => $currentTimeFormatted,
                    'group_code' => $oldProduct->group_code,
                    'last_updated' => $currentTimeFormatted,
                    'product_entry_date' => $currentTimeFormatted,
                    'product_code' => $oldProduct->product_code,
                    'product_grade_id' => $oldProduct->product_grade_id,
                    'product_group_id' => $oldProduct->product_group_id,
                    'product_model_id' => $oldProduct->product_model_id,
                    'product_name' => $oldProduct->product_name.' (ST)',
                    'product_unit_id' => $oldProduct->product_unit_id,
                    'sale_price' => $oldProduct->sale_price,
                    'purchase_price' => $request->rate[$key],
                    'supplier_info_id' => $oldProduct->supplier_info_id,
                    'store_info_id' => $request->to_store_id,
                ];
                DB::table('product_info')->insert($newProduct);
                $stock_transaction_data_in = [
                    'id' => $transaction->id+1,
                    'version' => 0,
                    'created_by' => 'Desktop PHP',
                    'date_created' => $currentTimeFormatted,
                    'last_updated' => $currentTimeFormatted,
                    'product_info_id' => $p->id+1,
                    'stock_transfer_id' => $stk_tr_id,
                    'transaction_date' => $formattedDate,
                    'store_info_id' => $request->to_store_id,
                    'specification' => 'Stock Transfer to stock',
                    'purchase_quantity' => +$request->quantity[$key],
                ];
            }
            DB::table('stock_transaction')->insert($stock_transaction_data_in);
            $transaction = DB::table('stock_transaction')->orderBy('id','desc')->first();
            $stock_transaction_data_out = [
                'id' => $transaction->id+1,
                'version' => 0,
                'created_by' => 'Desktop PHP',
                'date_created' => $currentTimeFormatted,
                'last_updated' => $currentTimeFormatted,
                'product_info_id' => $request->product_name[$key],
                'stock_transfer_id' => $stk_tr_id,
                'transaction_date' => $formattedDate,
                'store_info_id' => $request->from_store_id,
                'specification' => 'Stock Transfer from stock',
                'purchase_quantity' => -$request->quantity[$key],
            ];
            DB::table('stock_transaction')->insert($stock_transaction_data_out);

        }
        DB::table('last_id_info')
            ->where('id', 34) // Assuming 'id' is the primary key
            ->increment('last_id', 1);
        toastr()->success('New stock Transferred successfully!');
        return redirect()->back();

    }

    public function get_products(Request $request){

        $supplier_id = $request->supplier_id;
        $product_grade_id = $request->product_grade_id;
        $from_store_id = $request->from_store_id;
        $productData = DB::table('product_info')
            ->where('supplier_info_id', $supplier_id)
            ->where('product_grade_id', $product_grade_id)
            ->where('store_info_id',$from_store_id)
            ->get();

        return response()->json($productData);
    }
    public function get_product_size(Request $request){
        $model_id = $request->model_id;
        $product_models = DB::table('product_model')->where('id', $model_id)->get();
        return response()->json($product_models);
    }
    public function get_product_unit(Request $request){
        $unit_id = $request->unit_id;
        $product_units = DB::table('product_unit')->where('id', $unit_id)->get();
        return response()->json($product_units);
    }
    public function get_product_stock(Request $request){
        $product_id = $request->product_id;
        $from_store_id = $request->from_store_id;

        $stock = DB::table('stock_transaction')
            ->where('product_info_id', $product_id)
            ->where('store_info_id', $from_store_id)
            ->sum('purchase_quantity');
        return response()->json($stock);
    }
}
