
@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <h1>Stock Transfer</h1>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('stock_transfer_update',['id'=>$transfer->id]) }}">
            @csrf
            <div class="row justify-content-between">
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="form-group">
                        <label for="transfer_id">Transfer ID <span class="text-danger"> *</span></label>
                        <input type="text" name="transfer_id" value="{{$transfer->id}}" id="transfer_id" class="form-control" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="from_store_id">Source Store / Warehouse <span class="text-danger"> *</span></label>
                        <select  name="from_store_id" id="from_store_id" class="form-control" required>
                            <option value="">Select Source Store</option>
                            @foreach($sources as $source)
                                <option value="{{$source->id}}" @if($source_store->id == $source->id) selected @endif>{{$source->store_name}}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="form-group">
                        <label for="date">Date <span class="text-danger"> *</span></label>
                        <input type="text" name="date" value="{{$today}}" id="date" class="form-control" readonly required>
                    </div>
                    <div class="form-group">
                        <label for="to_store_id">To Destination <span class="text-danger"> *</span></label>
                        <select  name="to_store_id" id="to_store_id" class="form-control" required>
                            <option value="">Select Destination Store</option>
                            @foreach($sources as $source)
                                <option value="{{$source->id}}" @if($destination_store->id == $source->id) selected @endif>{{$source->store_name}}</option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>
            <div class="my-2">
                <button type="button" class="btn btn-primary" id="add-row">Add Row</button>
                <button type="button" class="btn btn-danger" id="remove-row">Remove Last Row</button>
            </div>

            <table class="table table-bordered" id="product_list_table">
                <thead>
                <tr>
                    <th width="150px">Supplier</th>
                    <th width="150px">Product Grade</th>
                    <th width="250px">Product Name</th>
                    <th width="120px">Size</th>
                    <th width="120px">Unit</th>
                    <th width="100px">Available Qnty</th>
                    <th width="100px">Transfer Qnty</th>
                    <th width="100px">Rate</th>
                    <th width="120px">Amount</th>
                </tr>
                </thead>
                <tbody>
                @foreach($transfer_items as $item)
                <tr>
                    <td>
                        <select name="suppliers[]" class="form-control">
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{$supplier->id}}" @if($supplier->id == getProductInfo($item->product_info_id)->supplier_info_id) selected @endif>{{$supplier->supplier_name}}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="product_grade[]" class="form-control">
                            <option value="">Select Grade</option>
                            @foreach($product_grades as $pg)
                                <option value="{{$pg->id}}" @if($pg->id == getProductInfo($item->product_info_id)->product_grade_id) selected @endif>{{$pg->grade}}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select name="product_name[]" class="form-control">
                            <option value="{{$item->product_info_id}}">{{getProductName($item->product_info_id)}}</option>
                        </select>
                    </td>
                    <td>
                        <select name="product_size[]" class="form-control">
                            <option value="{{getProductInfo($item->product_info_id)->product_model_id}}">{{getProductSize($item->product_info_id)}}</option>
                        </select>
                    </td>
                    <td>
                        <select name="product_unit[]" class="form-control">
                            <option value="{{getProductInfo($item->product_info_id)->product_unit_id}}">{{getProductUnit($item->product_info_id)}}</option>
                        </select>
                    </td>
                    <td><input type="text" value="{{getProductStock($item->product_info_id,$source_store->id) + $item->quantity}}" name="product_stock[]" class="form-control" readonly></td>
                    <td><input type="number" value="{{$item->quantity}}" step="any" name="quantity[]" class="form-control" required></td>
                    <td><input type="number" value="{{getProductUPrice($item->product_info_id)}}" step="any" name="rate[]" class="form-control" required ></td>
                    <td><input type="text" name="amount[]" value="{{getProductUPrice($item->product_info_id)*$item->quantity}}" class="form-control" required readonly></td>

                </tr>
                @endforeach
                </tbody>
            </table>


            <button type="submit" class="btn btn-success">Update Stock</button>
        </form>
    </div>

@endsection

@section('scripts')
    <script>
        // jQuery code for adding rows to the table
        $(document).ready(function () {
            $('#date').datepicker({
                dateFormat: 'dd-M-yy',
            });
            $("#add-row").click(function() {
                var newRow = $("#product_list_table tbody tr:last").clone(); // Clone the first row
                newRow.find("select").each(function() {
                    $(this).val(''); // Clear the selected option
                });
                newRow.find('input').each(function() {
                    $(this).val(''); // Clear the selected option
                });

                $("#product_list_table tbody").append(newRow); // Append the new row to the table
            });
            $("#remove-row").click(function() {
                var $rows = $("#product_list_table tbody tr");

                if ($rows.length > 1) {
                    $rows.last().remove();
                }
            });
            $('#from_store_id').on('change', function () {
                $("#product_list_table tbody tr:gt(0)").remove();
                $("#product_list_table tbody select[name='product_name[]']").empty();
                $("#product_list_table tbody select[name='product_size[]']").empty();
                $("#product_list_table tbody select[name='product_unit[]']").empty();
                $("#product_list_table tbody input[name='quantity[]']").val(''); // Clear input values
                $("#product_list_table tbody input[name='rate[]']").val(''); // Clear input values
                $("#product_list_table tbody input[name='amount[]']").val(''); // Clear input values
                $("#product_list_table tbody input[name='remarks[]']").val(''); // Clear input values
            });


            function loadProductList(row) {
                const from_store_id = $('#from_store_id').val();
                const supplierId = row.find('select[name="suppliers[]"]').val();
                const productGradeId = row.find('select[name="product_grade[]"]').val();


                // Make an AJAX request
                $.ajax({
                    url: '{{route('get_products')}}',
                    type: 'GET', // You can use GET or POST as appropriate
                    data: {
                        from_store_id: from_store_id,
                        supplier_id: supplierId,
                        product_grade_id: productGradeId,
                    },
                    success: function (data) {
                        // Update the select options and other fields
                        const productSelect = row.find('select[name="product_name[]"]');
                        productSelect.empty(); // Clear existing options
                        const option = '<option value="">Select Product</option>'
                        productSelect.append(option);
                        // Add new options based on the data received
                        $.each(data, function (key, value) {
                            productSelect.append($('<option>', {
                                value: value.id,
                                text: value.product_name,
                                'data-model_id' : value.product_model_id,
                                'data-unit_id' : value.product_unit_id,
                                'data-price' : value.purchase_price,
                            }));
                        });
                        // Update other fields as needed
                    }
                });
            }
            function loadProductSize(row) {

                const model_id = row.find('select[name="product_name[]"] option:selected').data('model_id');

                // Make an AJAX request
                $.ajax({
                    url: '{{route('get_product_size')}}',
                    type: 'GET', // You can use GET or POST as appropriate
                    data: {
                        model_id: model_id,
                    },
                    success: function (data) {
                        // Update the select options and other fields

                        const productSelect = row.find('select[name="product_size[]"]');
                        productSelect.empty();
                        // Add new options based on the data received
                        $.each(data, function (key, value) {
                            const option = '<option value="'+value.id+'">'+value.size_model+'</option>';
                            productSelect.append(option);
                        });
                        // Update other fields as needed
                    }
                });
            }
            function loadProductUnit(row) {

                const unit_id = row.find('select[name="product_name[]"] option:selected').data('unit_id');

                // Make an AJAX request
                $.ajax({
                    url: '{{route('get_product_unit')}}',
                    type: 'GET', // You can use GET or POST as appropriate
                    data: {
                        unit_id: unit_id,
                    },
                    success: function (data) {
                        // Update the select options and other fields

                        const productSelect = row.find('select[name="product_unit[]"]');
                        productSelect.empty();
                        // Add new options based on the data received
                        $.each(data, function (key, value) {
                            const option = '<option value="'+value.id+'">'+value.unit_name+'</option>';
                            productSelect.append(option);
                        });
                        // Update other fields as needed
                    }
                });
            }
            function loadProductStock(row) {

                const product_id = row.find('select[name="product_name[]"]').val();
                const from_store_id = $('#from_store_id').val();


                // Make an AJAX request
                $.ajax({
                    url: '{{route('get_product_stock')}}',
                    type: 'GET', // You can use GET or POST as appropriate
                    data: {
                        product_id: product_id,
                        from_store_id: from_store_id,
                    },
                    success: function (data) {
                        // Update the select options and other fields

                        const product_stock = row.find('input[name="product_stock[]"]');
                        product_stock.val(data);
                        const quantity = row.find('input[name="quantity[]"]');
                        quantity.attr({
                            "max" : data,
                            "min" : 1,
                        });
                        // Update other fields as needed
                    }
                });
            }

            $('#product_list_table').on('change', 'select[name="suppliers[]"], select[name="product_grade[]"]', function () {
                const row = $(this).closest('tr');
                loadProductList(row);
            });
            $('#product_list_table').on('change', 'select[name="product_name[]"]', function () {
                const row = $(this).closest('tr');
                const price = row.find('select[name="product_name[]"] option:selected').data('price');
                row.find('input[name="rate[]"]').val(price);
                loadProductSize(row);
                loadProductUnit(row);
                loadProductStock(row);
            });
            $('#product_list_table').on('input', 'input[name="quantity[]"], input[name="rate[]"]', function () {
                const row = $(this).closest('tr');
                const price = parseFloat(row.find('input[name="rate[]"]').val()) || 0; // Parse to float or default to 0 if null
                const qty = parseFloat(row.find('input[name="quantity[]"]').val()) || 0; // Parse to float or default to 0 if null
                const amount = price * qty;
                row.find('input[name="amount[]"]').val(amount);
            });

        });
    </script>
@endsection
