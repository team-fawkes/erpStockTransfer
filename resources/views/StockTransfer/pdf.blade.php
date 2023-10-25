<!DOCTYPE html>
<html>
<head>
    <title>Simple Blade View</title>
    <style>
        /* Internal CSS styles */
        html, body {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 0;
        }
        header {
            text-align: center;

        }

        h1 {
            font-size: 24px;
        }

        .container {
            padding: 10px;
            background-color: #fff;

        }

        p {
            font-size: 16px;
            line-height: 1.5;
        }
        #products {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 20px;
            background-color: #fff;

        }
        #bottom{
            width: 100%;
            text-align: center;
            border-spacing: 0;
            margin-bottom: 20px;
            margin-top: 50px;
        }
        #headtable, #headtable table{
            width: 100%;
            text-align: left;
            border-spacing: 0;
            margin-bottom: 20px;

        }
        #headtable table th, #headtable table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        #products th, #products td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        #bottom th, #bottom td {
             padding: 8px;
             border: 0px solid #ddd;
             text-align: center;
         }
        #bottom hr{
            margin: 10px 50px;
            border: 1px solid;
        }
        th, #products td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        #products th {
            background-color: #f2f2f2;
        }

        #products tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        @media (max-width: 768px) {
            #products {
                display: block;
                overflow-x: auto;
            }
            #products th, #products td {
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
<header>
    <h1>M/S Shathi Footwear</h1>
    <div>All kinds of Sanitary Supplier</div>
    <div>51, Alom Super Market, New Jurain, Postagula, Dhaka 1204</div>
    <hr>
</header>
<div class="container">
    <h3 style="text-align: center">Stock Transfer Invoice</h3>
    <table id="headtable">
        <tr>
            <td width="40%">
                <table>
                    <tr>
                        <td>Transfer ID</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Source Stock</td>
                        <td>{{$source_store->store_name}}</td>
                    </tr>
                    <tr>
                        <td>Destination Stock</td>
                        <td>{{$destination_store->store_name}}</td>
                    </tr>

                </table>

            </td>
            <td width="20%"></td>
            <td width="40%">
                <table>
                    <tr>
                        <td>Date</td>
                        <td>{{$transfer->transaction_date}}</td>
                    </tr>
                    <tr>
                        <td>Person</td>
                        <td>{{$transfer->created_by}}</td>
                    </tr>
                    <tr>
                        <td>Comments</td>
                        <td></td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
    <table id="products">
        <thead>
        <tr>
            <th>SL</th>
            <th>Product Name</th>
            <th>Grade</th>
            <th>Size</th>
            <th>Unit</th>
            <th>Rate</th>
            <th>Quantity</th>
            <th>Amount</th>
        </tr>
        </thead>
        <tbody>
        @php
            $sl = 1;
            $total_qty = 0;
            $total_amount = 0;
        @endphp
        @foreach($transfer_items as $item)
        <tr>
            <td>{{$sl++}}</td>
            <td>{{getProductName($item->product_info_id)}}</td>
            <td>{{getProductGrade($item->product_info_id)}}</td>
            <td>{{getProductSize($item->product_info_id)}}</td>
            <td>{{getProductUnit($item->product_info_id)}}</td>
            <td>{{getProductUPrice($item->product_info_id)}}</td>
            <td>{{$item->quantity}}</td>
            @php
                $total_qty +=$item->quantity;
                $total = round($item->quantity * getProductUPrice($item->product_info_id),2);
                $total_amount += $total;
            @endphp
            <td>{{$total}}</td>

        </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="6" style="text-align: right">Total</td>
            <td>{{$total_qty}}</td>
            <td>{{$total_amount}}</td>
        </tr>
        </tfoot>
    </table>

    <table id="bottom">
        <tr>
            <td width="33%"><hr>Received By</td>
            <td width="33%"><hr>Operator</td>
            <td width="33%"><hr>Authorised Signature</td>
        </tr>
    </table>

</div>
</body>
</html>
