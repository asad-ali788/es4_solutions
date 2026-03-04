<div wire:init="loadCharts" class="w-100">

    {{-- ✅ SKELETON PLACE (shows while loadCharts is running) --}}
    {{-- Skeleton --}}
    <div wire:loading wire:target="loadCharts" class="w-100">
        <div class="w-100">
            @include('livewire.dashboard.skeleton.performance-charts-skeleton')
        </div>
    </div>

    {{-- ✅ REAL CONTENT (shows after loadCharts is finished) --}}
    <div wire:loading.remove wire:target="loadCharts">
        <div class="row g-3 mt-2 align-items-stretch">
            <!-- Daily Performance -->
            <div class="col-12 col-md-4 d-flex">
                <div class="card w-100 mb-0">
                    <div class="card-body mb-0 pb-0">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar-xs me-2">
                                <span class="avatar-title rounded-circle glance glance-success font-size-18">
                                    <i class="bx bx-pie-chart-alt"></i>
                                </span>
                            </div>
                            <h5 class="font-size-14 mb-0">Daily Performance last 7 days</h5>
                        </div>
                        <p class="text-muted font-size-12 mb-0 mt-0">Product Performance Report - SP + SB + SD</p>
                        <div id="dailyChart" style="min-height: 250px;"></div>
                    </div>
                </div>
            </div>

            <!-- Weekly Performance -->
            <div class="col-12 col-md-4 d-flex">
                <div class="card w-100 mb-0">
                    <div class="card-body mb-0 pb-0">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar-xs me-2">
                                <span class="avatar-title rounded-circle glance glance-success font-size-18">
                                    <i class="bx bx-pie-chart-alt"></i>
                                </span>
                            </div>
                            <h5 class="font-size-14 mb-0">Weekly Performance last 4 weeks</h5>
                        </div>
                        <p class="text-muted font-size-12 mb-0 mt-0">Product Performance Report - SP + SB + SD</p>
                        <div id="weeklyChart" style="min-height: 250px;"></div>
                    </div>
                </div>
            </div>

            <!-- Monthly Performance -->
            <div class="col-12 col-md-4 d-flex">
                <div class="card w-100 mb-0">
                    <div class="card-body mb-0 pb-0">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar-xs me-2">
                                <span class="avatar-title rounded-circle glance glance-success font-size-18">
                                    <i class="bx bx-pie-chart-alt"></i>
                                </span>
                            </div>
                            <h5 class="font-size-14 mb-0">Monthly Performance last 3 months</h5>
                        </div>
                        <p class="text-muted font-size-12 mb-0 mt-0">Product Performance Report - SP + SB + SD</p>
                        <div id="monthlyChart" style="min-height: 250px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script src="{{ asset('assets/js/charts.js') }}"></script>

        <script>
            // ✅ Render charts only when Livewire says data is ready
            document.addEventListener('livewire:init', () => {
                Livewire.on('adsChartsReady', (payload) => {
                    const charts = payload.charts || {};

                    renderChart(charts.daily ?? [], "#dailyChart", "Daily Performance last 7 days");
                    renderChart(charts.weekly ?? [], "#weeklyChart", "Weekly Performance last 4 weeks");
                    renderChart(charts.monthly ?? [], "#monthlyChart", "Monthly Performance last 3 months");
                });
            });
        </script>
    @endpush

</div>
