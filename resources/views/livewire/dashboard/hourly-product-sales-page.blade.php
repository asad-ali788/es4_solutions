<div class="container-fluid" wire:init="bootPage">
    <style>
        /* Base row */
        .table tbody tr.product-row {
            cursor: pointer;
            transition: background-color .15s ease, box-shadow .15s ease;
        }

        /* Hover (all modes) */
        .table tbody tr.product-row:hover>td {
            background-color: rgba(0, 0, 0, 0.035);
        }

        /* SELECTED — LIGHT MODE */
        html[data-bs-theme="light"] .table tbody tr.product-row.is-selected>td,
        body[data-bs-theme="light"] .table tbody tr.product-row.is-selected>td {
            background-color: rgba(85, 110, 230, 0.14) !important;
        }

        /* SELECTED — DARK MODE */
        html[data-bs-theme="dark"] .table tbody tr.product-row.is-selected>td,
        body[data-bs-theme="dark"] .table tbody tr.product-row.is-selected>td {
            background-color: rgba(85, 110, 230, 0.28) !important;
        }

        /* Left indicator (Skote style) */
        .table tbody tr.product-row.is-selected>td:first-child {
            box-shadow: inset 4px 0 0 #556ee6;
        }

        /* Text emphasis */
        .table tbody tr.product-row.is-selected td {
            font-weight: 500;
        }

        /* Hover while selected */
        .table tbody tr.product-row.is-selected:hover>td {
            background-color: rgba(85, 110, 230, 0.2) !important;
        }

        .pagination {
            margin-bottom: 0;
        }

        .page-item .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 .75rem;
        }

        .page-link svg {
            width: 16px;
            height: 16px;
        }
    </style>

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">
                    Hourly Sales Snapshot
                </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.dashboard') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Dashboard
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Overlay Chart (TOP) --}}
    <div class="card mb-3">
        <div class="card-body position-relative" style="min-height: 380px;">

            {{-- REAL Header (backend rendered; hide while loading) --}}
            <div wire:loading.remove wire:target="loadChart,bootPage">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar-xs me-2">
                        <span class="avatar-title rounded-circle glance glance-primary font-size-18">
                            <i class="bx bx-line-chart px-2"></i>
                        </span>
                    </div>
                    <div>
                        <div class="fw-semibold">{{ $chartTitle ?: '—' }}</div>
                        <div class="text-muted small">{{ $chartSubtitle ?: '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- Chart --}}
            <div wire:loading.remove wire:target="loadChart,bootPage">
                <div class="position-relative" style="min-height:320px;">
                    <div wire:ignore>
                        <div id="hourlyProductOverlayChart" style="min-height:320px;"></div>
                    </div>

                    <div id="hourlyOverlayEmpty"
                        class="d-none flex-column align-items-center justify-content-center text-center text-muted small"
                        style="position:absolute; inset:0;">
                        <div class="mb-1">No data for current filters</div>
                    </div>
                </div>
            </div>

            {{-- Skeleton overlay (covers header + chart) --}}
            <div wire:loading.flex wire:target="loadChart,bootPage"
                class="position-absolute top-0 start-0 w-100 h-100 align-items-center justify-content-center bg-body"
                style="z-index: 10;">
                <div class="w-100">
                    @include('livewire.dashboard.skeleton.hourly-snapshot-chart-skeleton')
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">

                {{-- Date --}}
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label mb-0 small text-muted">Date</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bx bx-calendar"></i>
                        </span>
                        <input type="date" class="form-control" wire:model.live="date">
                    </div>
                </div>

                {{-- Sales Channel --}}
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label mb-0 small text-muted">Channel</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bx bx-store"></i>
                        </span>
                        <select class="form-select" wire:model.live="salesChannel">
                            <option value="">All</option>
                            @foreach ($channels as $ch)
                                <option value="{{ $ch }}">{{ $ch }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Search --}}
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12">
                    <label class="form-label mb-0 small text-muted">ASIN / SKU / Product Name</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bx bx-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Search ASIN or SKU or Product"
                            wire:model.live.debounce.400ms="search">
                    </div>
                </div>

                {{-- Clear All --}}
                <div class="col-xl-1 col-lg-2 col-md-3 col-sm-6 d-flex align-items-end mt-4">
                    <button type="button"
                        class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-1"
                        wire:click="clearAll" wire:loading.attr="disabled">
                        <i class="bx bx-reset"></i>
                        <span wire:loading.remove wire:target="clearAll">Clear</span>
                        <span wire:loading wire:target="clearAll">Clearing…</span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- Product table --}}
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold">
                    Products ({{ $tableLabelCurrent }} vs {{ $tableLabelPrev }}) — click row to toggle selection
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product Name</th>
                            <th>ASIN</th>
                            <th>SKU</th>

                            <th class="text-end">Units - {{ $tableLabelCurrent }}</th>
                            <th class="text-end">Sales - {{ $tableLabelCurrent }}</th>

                            <th class="text-end">Units - {{ $tableLabelPrev }}</th>
                            <th class="text-end">Sales - {{ $tableLabelPrev }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($products as $row)
                            @php
                                $isSelected =
                                    ($selectedAsin && $row->asin === $selectedAsin) ||
                                    (!$selectedAsin && $selectedSku && $row->sku === $selectedSku);
                            @endphp

                            <tr role="button" class="product-row {{ $isSelected ? 'is-selected' : '' }}"
                                wire:click="selectProduct(@js($row->asin), @js($row->sku))">
                                <td>{{ $row->child_short_name ?? '-' }}</td>

                                <td class="font-monospace">{{ $row->asin }}</td>
                                <td class="font-monospace">{{ $row->sku }}</td>

                                <td class="fw-semibold text-end">
                                    {{ number_format((int) $row->units_today) }}
                                </td>

                                <td class="text-end fw-semibold text-success">
                                    ${{ number_format((float) $row->sales_today, 2) }}
                                </td>

                                <td class="text-end text-muted">
                                    {{ number_format((int) $row->units_yesterday) }}
                                </td>

                                <td class="text-end fw-semibold text-primary">
                                    ${{ number_format((float) $row->sales_yesterday, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No products found for current filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrap ms-auto">
                {{ $products->links() }}
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

                const CHART_ID = 'hourlyProductOverlayChart';
                const EMPTY_ID = 'hourlyOverlayEmpty';

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

                function fmtRangeLabel(minuteOfDay) {
                    const start = Math.max(0, Math.min(1439, Number(minuteOfDay) || 0));
                    const end = Math.min(1440, start + 60);

                    function fmt(m) {
                        let h = Math.floor(m / 60);
                        const mm = m % 60;
                        const ampm = h >= 12 ? 'PM' : 'AM';
                        h = h % 12;
                        h = h === 0 ? 12 : h;
                        return `${String(h).padStart(2,'0')}:${String(mm).padStart(2,'0')} ${ampm}`;
                    }

                    const endLabel = end === 1440 ? '12:00 AM' : fmt(end);
                    return `${fmt(start)} – ${endLabel}`;
                }

                function buildOptions(payload) {
                    const series = payload.series ?? [];

                    return {
                        chart: {
                            type: 'area',
                            height: 320,
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
                                seriesIndex,
                                dataPointIndex,
                                w
                            }) {
                                const x = w.globals.seriesX?.[seriesIndex]?.[dataPointIndex];
                                if (x == null) return '';

                                const timeLabel = fmtRangeLabel(x);

                                const rows = w.globals.seriesNames.map((name, si) => {
                                    const xs = w.globals.seriesX?.[si] || [];
                                    const idx = xs.indexOf(x);
                                    const y = idx >= 0 ? w.globals.series?.[si]?.[idx] : null;

                                    const color = w.globals.colors?.[si] || '#999';
                                    const value = y == null ?
                                        '<span class="text-muted">–</span>' :
                                        `<strong>${Math.round(y).toLocaleString()}</strong>`;

                                    return `
                                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;font-size:13px;">
                                            <div style="display:flex;align-items:center;gap:6px;">
                                                <span style="width:8px;height:8px;border-radius:50%;background:${color};display:inline-block;"></span>
                                                <span>${name}</span>
                                            </div>
                                            <span>${value}</span>
                                        </div>
                                    `;
                                }).join('');

                                return `
                                    <div style="background:#1e1e2f;color:#fff;padding:10px 12px;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,.35);min-width:190px;">
                                        <div style="font-weight:600;font-size:14px;margin-bottom:6px;border-bottom:1px solid rgba(255,255,255,.15);padding-bottom:4px;">
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
                            strokeDashArray: 4
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'left'
                        }
                    };
                }

                function render(payload) {
                    const el = document.getElementById(CHART_ID);
                    if (!el || typeof ApexCharts === 'undefined') return;

                    const series = payload.series ?? [];

                    if (!Array.isArray(series) || series.length === 0) {
                        destroyChart();
                        toggleEmpty(true);
                        return;
                    }

                    toggleEmpty(false);

                    if (!chart) {
                        chart = new ApexCharts(el, buildOptions(payload));
                        chart.render();
                        return;
                    }

                    chart.updateSeries(series, true);
                }

                window.addEventListener('hourlyProductOverlayReady', (e) => {
                    const payload = e.detail?.payload ?? e.detail ?? {};
                    requestAnimationFrame(() => render(payload));
                });

                document.addEventListener('livewire:navigating', () => destroyChart());

                // Scroll preservation (pagination + any Livewire updates)
                document.addEventListener('click', (evt) => {
                    const a = evt.target.closest('.pagination a');
                    if (!a) return;
                    sessionStorage.setItem('hourly_sales_snapshot_scroll_y', String(window.scrollY));
                });

                document.addEventListener('livewire:init', () => {
                    if (!window.Livewire || typeof window.Livewire.hook !== 'function') return;

                    try {
                        window.Livewire.hook('message.processed', () => {
                            const y = sessionStorage.getItem('hourly_sales_snapshot_scroll_y');
                            if (y === null) return;
                            sessionStorage.removeItem('hourly_sales_snapshot_scroll_y');

                            const yy = Number(y);
                            if (!Number.isFinite(yy)) return;

                            requestAnimationFrame(() => window.scrollTo({
                                top: yy,
                                left: 0,
                                behavior: 'instant'
                            }));
                        });
                    } catch (e) {}
                });
            })();
        </script>
    @endpush
</div>
