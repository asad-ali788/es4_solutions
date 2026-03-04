@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Preview ASIN Forecast Upload</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-2">

                    {{-- Action bar --}}

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    @foreach (array_keys($rows[0]) as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        @foreach ($row as $cell)
                                            <td>{{ is_string($cell) ? e($cell) : $cell }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Action buttons below table --}}
                    <div class="d-flex justify-content-end mb-2 gap-2">
                        <form method="POST" action="{{ route('admin.orderforecastasin.confirmBulkUpload') }}">
                            @csrf
                            <input type="hidden" name="cache_key" value="{{ $cacheKey }}">
                            <input type="hidden" name="order_forecast_id" value="{{ $forecastId }}">
                            <button type="submit" class="btn btn-success">Confirm & Process</button>
                        </form>

                        <form method="POST" action="{{ route('admin.orderforecastasin.cancelBulkUpload') }}">
                            @csrf
                            <input type="hidden" name="cache_key" value="{{ $cacheKey }}">
                            <input type="hidden" name="order_forecast_id" value="{{ $forecastId }}">
                            <button type="submit" class="btn btn-danger">Cancel</button>
                        </form>
                    </div>


                    {{-- Updated context note --}}
                    <p class="text-muted mt-3">
                        <span class="badge badge-soft-warning">Important :</span>
                        Review the ASINs and monthly forecast values carefully.
                        Once confirmed, the data will be processed and cannot be undone.
                    </p>

                </div>
            </div>
        </div>
    </div>
@endsection
