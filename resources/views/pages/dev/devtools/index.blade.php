@push('style')
    <style>
        /* Equal card height */
        .system-tool-card {
            transition: transform 0.12s ease-out, box-shadow 0.12s ease-out;
        }
        .system-tool-card:hover {
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.18);
        }
        /* Icon circle – small, centered, with glow */
        .system-tool-card .icon-wrap {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }
        /* Icon size */
        .system-tool-card i {
            font-size: 2.4rem;
            color: #fff;
        }
        /* Gradient icon backgrounds + glow */
        .pulse-card .icon-wrap {
            background: linear-gradient(135deg, #c084fc, #a855f7);
            box-shadow: 0 0 22px rgba(168, 85, 247, 0.45);
        }
        .log-card .icon-wrap {
            background: linear-gradient(135deg, #34d399, #10b981);
            box-shadow: 0 0 22px rgba(16, 185, 129, 0.45);
        }
        .supervisor-card .icon-wrap {
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            box-shadow: 0 0 22px rgba(59, 130, 246, 0.45);
        }
        /* Optional: slightly stronger label look for tabs */
        .system-tools-tabs .nav-link {
            font-weight: 500;
        }
    </style>
@endpush
<div class="row mb-0">
    {{-- Pulse --}}
    @can('viewPulse')
        <div class="col-xl-4 col-md-6 mb-3">
            <a href="{{ config('app.url') }}/pulse" target="_blank" class="text-reset text-decoration-none">
                <div class="card system-tool-card pulse-card">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <div class="icon-wrap">
                            <i class="bx bx-pulse"></i>
                        </div>
                        <h4 class="mb-1">Pulse</h4>
                        <p class="mb-0 text-muted">
                            Application health & performance metrics.
                        </p>
                    </div>
                </div>
            </a>
        </div>
    @endcan
    {{-- Log Viewer --}}
    @can('viewLogViewer')
        <div class="col-xl-4 col-md-6 mb-3">
            <a href="{{ config('app.url') }}/log-viewer" target="_blank" class="text-reset text-decoration-none">
                <div class="card system-tool-card log-card">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <div class="icon-wrap">
                            <i class="bx bx-data"></i>
                        </div>
                        <h4 class="mb-1">Log Viewer</h4>
                        <p class="mb-0 text-muted">
                            Browse and filter application logs.
                        </p>
                    </div>
                </div>
            </a>
        </div>
    @endcan
    {{-- Supervisor --}}
    @can('developer.supervisor')
        <div class="col-xl-4 col-md-6 mb-3">
            <a href="{{ config('app.url') }}/supervisor/" target="_blank" class="text-reset text-decoration-none">
                <div class="card system-tool-card supervisor-card">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <div class="icon-wrap">
                            <i class="bx bx-shield-alt-2"></i>
                        </div>
                        <h4 class="mb-1">Supervisor</h4>
                        <p class="mb-0 text-muted">
                            Manage and monitor queue workers.
                        </p>
                    </div>
                </div>
            </a>
        </div>
    @endcan
</div>
