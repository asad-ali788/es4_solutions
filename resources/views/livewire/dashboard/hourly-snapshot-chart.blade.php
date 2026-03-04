<div wire:init="loadHourlyCharts" class="w-100">

    {{-- Skeleton while loading --}}
    <div wire:loading wire:target="loadHourlyCharts" class="w-100">
        @include('livewire.dashboard.skeleton.hourly-snapshot-chart-skeleton')
    </div>

    {{-- Actual card after loading --}}
    <div wire:loading.remove wire:target="loadHourlyCharts" class="card w-100 mb-0">
        <div class="card-body pb-0">
            <div class="d-flex align-items-center">
                <div class="avatar-xs me-2">
                    <span class="avatar-title rounded-circle glance glance-primary font-size-18">
                        <i class="bx bx-line-chart px-2"></i>
                    </span>
                </div>
                <div class="d-flex align-items-center justify-content-between w-100">
                    <a href="{{ route('admin.dashboard.hourly-sales.products') }}"
                        class="text-reset d-flex align-items-center gap-2" title="Expand view">
                        <h5 class="font-size-14 mb-0">Hourly Sales Snapshot Chart</h5>
                    </a>

                    <a href="{{ route('admin.dashboard.hourly-sales.products') }}" class="text-muted"
                        title="Expand view">
                        <span class="avatar-title rounded-circle glance glance-light font-size-18"
                            data-bs-toggle="tooltip" title="Detailed view">
                            <i class="bx bx-right-arrow-alt p-1"></i>
                        </span>
                    </a>
                </div>

            </div>

            <div class="mt-3 position-relative" style="min-height:250px;">
                <div wire:ignore>
                    <div id="hourlySnapshotChart" style="min-height:250px;"></div>
                </div>

                <div id="hourlyEmptyState"
                    class="d-none flex-column align-items-center justify-content-center text-center text-muted small"
                    style="position:absolute; inset:0;">
                    <div class="mb-1">No data available now</div>
                </div>
            </div>
        </div>
    </div>

    @once
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        @endpush
    @endonce

    @push('scripts')
        <script>
            (function() {
                let chart = null;

                const CHART_ID = 'hourlySnapshotChart';
                const EMPTY_ID = 'hourlyEmptyState';

                function toggleEmpty(show) {
                    const el = document.getElementById(EMPTY_ID);
                    if (!el) return;
                    el.classList.toggle('d-none', !show);
                    el.classList.toggle('d-flex', show);
                }

                function destroyChart() {
                    if (!chart) return;
                    try {
                        chart.destroy();
                    } catch (e) {}
                    chart = null;
                }

                function buildOptions(series, height) {
                    return {
                        chart: {
                            type: 'area',
                            height,
                            fontFamily: 'inherit',
                            redrawOnWindowResize: true,
                            redrawOnParentResize: true,
                            zoom: {
                                enabled: true,
                                type: 'x',
                                autoScaleYaxis: true
                            },
                            toolbar: {
                                show: true,
                                tools: {
                                    download: false,
                                    selection: true,
                                    zoom: true,
                                    zoomin: true,
                                    zoomout: true,
                                    pan: true,
                                    reset: true
                                }
                            }
                        },

                        series,

                        xaxis: {
                            type: 'numeric',
                            min: 0,
                            max: 1439,
                            tickAmount: 6,
                            labels: {
                                formatter: (val) => {
                                    const min = Math.max(0, Math.min(1439, Number(val) || 0));
                                    const h = Math.floor(min / 60);
                                    return String(h).padStart(2, '0') + ':00';
                                }
                            }
                        },

                        tooltip: {
                            shared: false,
                            intersect: false,
                            custom: function({
                                series,
                                seriesIndex,
                                dataPointIndex,
                                w
                            }) {
                                const x = w.globals.seriesX?.[seriesIndex]?.[dataPointIndex];
                                if (x == null) return '';

                                const min = Math.max(0, Math.min(1439, Number(x)));
                                let h = Math.floor(min / 60);
                                const m = min % 60;

                                const ampm = h >= 12 ? 'PM' : 'AM';
                                h = h % 12;
                                h = h === 0 ? 12 : h;

                                const startMin = min;
                                const endMin = Math.min(1439, startMin + 60);

                                function fmt(mins) {
                                    let h = Math.floor(mins / 60);
                                    let m = mins % 60;
                                    const ampm = h >= 12 ? 'PM' : 'AM';
                                    h = h % 12;
                                    h = h === 0 ? 12 : h;
                                    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')} ${ampm}`;
                                }

                                const timeLabel = `${fmt(startMin)} – ${fmt(endMin)}`;

                                const rows = w.globals.seriesNames.map((name, si) => {
                                    const xs = w.globals.seriesX?.[si] || [];
                                    const idx = xs.indexOf(x);
                                    const y = idx >= 0 ? w.globals.series?.[si]?.[idx] : null;

                                    const color = w.globals.colors?.[si] || '#999';
                                    const value = y == null ?
                                        '<span class="text-muted">–</span>' :
                                        `<strong>${Math.round(y).toLocaleString()}</strong>`;

                                    return `
                                        <div style="
                                            display:flex;
                                            justify-content:space-between;
                                            align-items:center;
                                            margin-top:6px;
                                            font-size:13px;
                                        ">
                                            <div style="display:flex;align-items:center;gap:6px;">
                                                <span style="
                                                    width:8px;
                                                    height:8px;
                                                    border-radius:50%;
                                                    background:${color};
                                                    display:inline-block;
                                                "></span>
                                                <span>${name}</span>
                                            </div>
                                            <span>${value}</span>
                                        </div>
                                    `;
                                }).join('');

                                return `
                                    <div style="
                                        background:#1e1e2f;
                                        color:#fff;
                                        padding:10px 12px;
                                        border-radius:10px;
                                        box-shadow:0 6px 20px rgba(0,0,0,.35);
                                        min-width:170px;
                                    ">
                                        <div style="
                                            font-weight:600;
                                            font-size:14px;
                                            margin-bottom:6px;
                                            border-bottom:1px solid rgba(255,255,255,.15);
                                            padding-bottom:4px;
                                        ">
                                            ${timeLabel}
                                        </div>
                                        ${rows}
                                    </div>
                                `;
                            }
                        },

                        stroke: {
                            curve: 'smooth',
                            width: 2.5
                        },
                        dataLabels: {
                            enabled: false
                        },
                        markers: {
                            size: 4,
                            strokeWidth: 2,
                            hover: {
                                size: 6
                            }
                        },

                        fill: {
                            type: 'gradient',
                            opacity: [0.6, 0.35],
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 1,
                                opacityTo: 0,
                                stops: [0, 96, 100]
                            }
                        },

                        colors: ['#ffc107', '#008FFB'],

                        yaxis: {
                            decimalsInFloat: 0,
                            labels: {
                                formatter: (v) => (v == null ? '' : Math.round(v).toLocaleString())
                            }
                        },

                        grid: {
                            strokeDashArray: 4,
                            padding: {
                                left: 6,
                                right: 10
                            }
                        },

                        legend: {
                            position: 'top',
                            horizontalAlign: 'left'
                        }
                    };
                }

                function renderChart(series) {
                    const el = document.getElementById(CHART_ID);
                    if (!el || typeof ApexCharts === 'undefined') return;

                    if (!Array.isArray(series) || series.length === 0) {
                        destroyChart();
                        toggleEmpty(true);
                        return;
                    }

                    toggleEmpty(false);
                    destroyChart();

                    chart = new ApexCharts(el, buildOptions(series, 260));
                    chart.render();
                }

                // Livewire event: hourlySnapshotReady { series: [...] }
                window.addEventListener('hourlySnapshotReady', (e) => {
                    const series = e.detail?.series ?? [];
                    requestAnimationFrame(() => renderChart(series));
                });

                // Cleanup on navigation
                document.addEventListener('livewire:navigating', () => {
                    destroyChart();
                });
            })();
        </script>
    @endpush

</div>
