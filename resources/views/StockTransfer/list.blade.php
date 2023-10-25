@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Edit</th>
                        <th>Date</th>
                        <th>Tr. No</th>
                        <th>Source Stock</th>
                        <th>Destination Stock</th>
                        <th>Total Trans. Qty</th>
                        <th>Total Amount</th>
                        <th>User Name</th>
                        <th>Print</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($transfers as $transfer)
                        <tr>
                            <td><a href="{{route('transfer_edit',['id'=>$transfer->id])}}"><i class="fas fa-edit"></i> </a> </td>
                            <td>{{date('d-M-Y',strtotime($transfer->transaction_date))}}</td>
                            <td>{{$transfer->id}}</td>
                            <td>{{$transfer->source_store_name}}</td>
                            <td>{{$transfer->destination_store_name}}</td>
                            <td>{{$transfer->total_quantity}}</td>
                            <td>{{getTotalTransferPrice($transfer->id)}}</td>
                            <td>{{$transfer->created_by}}</td>
                            <td><a href="{{route('stock_transfer_print',['id'=>$transfer->id])}}"><i class="fas fa-print"></i></a> </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).ready( function () {
            $('table').DataTable();
        });
    </script>
@endsection
