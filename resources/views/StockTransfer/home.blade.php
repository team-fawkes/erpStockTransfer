@extends('layouts.app')

@section('content')
    <div class="container">
        <a href="{{route('stock_transfer')}}" class="btn btn-primary">Stock Transfer</a>
        <a href="{{route('stock_transfer_list')}}" class="btn btn-info">Stock Transfer List</a>
    </div>
@endsection
