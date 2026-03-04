<div> <!-- ROOT WRAPPER -->
    <!-- Search -->
    <div class="row mb-2 g-2 align-items-center">

        {{-- Search --}}
        <div class="col-12 col-sm-6 col-lg-4">
            <form wire:submit.prevent="$refresh">
                <div x-data class="w-100">
                    <div class="search-box d-flex align-items-center position-relative bg-light px-2 py-2 w-100"
                        style="min-width: 200px; max-width: 360px;">

                        <i class="bx bx-search-alt search-icon"></i>

                        <input type="text" id="search-search" class="form-control border-0 bg-light flex-grow-1"
                            placeholder="Enter to Search ..." wire:model.debounce.400ms="search">

                        <button type="button" class="btn-clear bg-light border-0 position-absolute end-0 me-2"
                            x-show="$wire.search.length > 0" @click="$wire.set('search', '')">
                            <i class="bx bx-x fs-5"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Button --}}
        @can('order_forecast.create')
            <div class="col-12 col-sm-6 col-lg-auto ms-lg-auto text-sm-end">

                @if ($this->metricsNotReady)
                    <button class="btn btn-success btn-rounded w-100 w-sm-auto" disabled
                        title="Metrics are being processed">
                        <i class="mdi mdi-timer-sand me-1"></i>
                        New Forecast
                    </button>

                    <div class="text-danger fw-bold small mt-1">
                        Metrics aren’t ready yet. Try again sometime.
                    </div>
                @else
                    <a href="{{ route('admin.orderforecast.create') }}" class="btn btn-success btn-rounded w-100 w-sm-auto">
                        <i class="mdi mdi-plus me-1"></i>
                        New Forecast
                    </a>
                @endif

            </div>
        @endcan


    </div>


    <!-- Table -->
    <div class="table-responsive">
        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
            <thead class="table-light">
                <tr>
                    <th>Forecast Name</th>
                    <th>Order Date</th>
                    <th>Notes</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse($forecasts as $forecast)
                    @livewire('forecast.forecast-row', ['forecast' => $forecast], key($forecast->id))
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No forecast records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-3">
        {{ $forecasts->links('pagination::bootstrap-5') }}
    </div>

    <p class="text-muted mt-3">
        <span class="badge badge-soft-info">Note :</span> Click on the <span class="text-success fw-bold">Forecast
            Name</span> to
        view
        detailed forecast snapshots and insights.
    </p>

</div> <!-- END ROOT WRAPPER -->
