@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Update Assigned Asin to User</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.assignAsin.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Assign ASIN
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">

                    <h4 class="card-title">Assign ASIN to User</h4>
                    <p class="card-title-desc">Select the Asin from the Multi-dropdown Or Upload as Excel sheet</p>
                    <form action="{{ route('admin.assignAssin.store', $user?->id) }}" method="POST" id="userAddForm">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <input type="text" name="user_id" class="form-control" value="{{ $user->id ?? '' }}"
                                        hidden>
                                    <label class="form-label">Name</label>
                                    <input type="text" name="user_name" class="form-control"
                                        value="{{ $user->name ?? '' }}" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Select ASIN(s)</label>
                                    <select name="asins[]" class="form-select asin-select" multiple="multiple"
                                        style="width: 100%;">
                                        @foreach ($assignedAsins as $asin)
                                            <option value="{{ $asin }}" selected>{{ $asin }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="text-end mb-3">
                                <button class="btn btn-success waves-effect waves-light btn-rounded" type="submit">
                                    {{ $user ? 'Update Asins' : 'Create User' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Upload as Excel</h4>
                        <p class="card-title-desc">Replaces all existing ASINs</p>
                        <form action="{{ route('admin.assignAssin.import') }}" method="POST" enctype="multipart/form-data"
                            class="hstack gap-3">
                            @csrf
                            <input type="text" name="user_id" class="form-control" value="{{ $user->id ?? '' }}" hidden>
                            <input type="file" name="file" placeholder="Add your item here..."
                                aria-label="Add your item here..." class="form-control me-auto" required>
                            @error('file')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                            <button type="submit" class="btn btn-success btn-rounded">
                                Submit
                            </button>
                            <div class="vr"></div>
                            <a href="{{ route('admin.assignAssin.example.asins') }}"
                                class="d-flex align-items-center text-decoration-none">
                                <i class="mdi mdi-download me-1"></i>
                                <span>Example</span>
                            </a>
                        </form>
                    </div>
                </div>
            </div>
            <!-- end select2 -->
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Assigned ASIN List</h4>
                    <div class="table-responsive" data-simplebar="init" style="max-height: 425px;">
                        <table class="table table-sm align-middle mb-0">
                            <tbody>
                                @forelse ($assignedAsins as $asin)
                                    <tr style="line-height: 1.2;">
                                        <td style="width: 20%;">
                                            <h6 class="mb-0 text-truncate" style="font-size: 12px;">{{ $loop->iteration }}
                                            </h6>
                                        </td>
                                        <td class=" p-1 pe-2" style="width: 80%;">
                                            <h5 style="font-size: 14px; font-weight: 500;">
                                                {{ $asin }}
                                            </h5>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted" style="font-size: 14px;">
                                            No ASINs assigned yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof $.fn.select2 === 'undefined') {
                    console.error("Select2 not loaded!");
                    return;
                }

                // Pass PHP array to JS
                const assignedAsins = @json($assignedAsins ?? []);

                // Init Select2 with AJAX + preloaded data
                $('.asin-select').select2({
                    placeholder: "Search ASIN",
                    minimumInputLength: 0,
                    ajax: {
                        url: '{{ route('admin.assignAssin.search') }}',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term || '',
                                page: params.page || 1
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.results,
                                pagination: {
                                    more: data.pagination.more
                                }
                            };
                        }
                    },
                    width: '100%',
                    // Tell Select2 about initial data so pre-selected options show
                    data: assignedAsins.map(asin => ({
                        id: asin,
                        text: asin
                    }))
                });

                // Append assigned ASINs as selected options if missing
                assignedAsins.forEach(function(asin) {
                    if (!$(".asin-select option[value='" + asin + "']").length) {
                        const newOption = new Option(asin, asin, true, true);
                        $('.asin-select').append(newOption).trigger('change');
                    }
                });
            });
        </script>
    @endpush
@endsection
