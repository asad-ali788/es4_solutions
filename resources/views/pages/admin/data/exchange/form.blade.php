@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">{{ $currency ? 'Edit Exchange' : 'Create Exchange' }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.data.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Data
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
            
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{ $currency ? 'Update Exchange Details' : 'Add New Exchange' }}</h4>
                    <form action="{{ $currency ? route('admin.currencies.update', $currency->id) : route('admin.currencies.store') }}" method="POST">
                        @csrf
                        @if($currency)
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Country Code</label>
                                    <input type="text" name="country_code" class="form-control" value="{{ old('country_code', $currency->country_code ?? '') }}" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Currency Code</label>
                                    <input type="text" name="currency_code" class="form-control" value="{{ old('currency_code', $currency->currency_code ?? '') }}" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Currency Symbol</label>
                                    <input type="text" name="currency_symbol" class="form-control" value="{{ old('currency_symbol', $currency->currency_symbol ?? '') }}">
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Currency Name</label>
                                    <input type="text" name="currency_name" class="form-control" value="{{ old('currency_name', $currency->currency_name ?? '') }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Conversion Rate to USD</label>
                                    <input type="number" step="0.01" name="conversion_rate_to_usd" class="form-control" value="{{ old('conversion_rate_to_usd', $currency->conversion_rate_to_usd ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button class="btn btn-success waves-effect waves-light btn-rounded" type="submit">{{ $currency ? 'Update' : 'Create' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
