@extends('layouts.app')

@section('content')
<div class="container">
    <h1>All Product Stocks</h1>
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>SKU</th>
                @foreach ($warehouses as $warehouse)
                    <th>{{ $warehouse->warehouse_name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
                <tr>
                    <td>{{ $row['sku'] }}</td>
                    @foreach ($warehouses as $warehouse)
                        <td>{{ $row['warehouse_stock_' . $warehouse->id.'_'.$row['sku']] }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection


