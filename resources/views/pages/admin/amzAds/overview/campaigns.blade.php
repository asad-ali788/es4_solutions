@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">
                    Amazon Ads - Campaign Overview
                </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        @php
                            $asins = request('asins', []);
                            $asin = is_array($asins) ? $asins[0] ?? null : $asins;
                        @endphp
                        <li class="breadcrumb-item active">
                            @if (request('source') === 'ads-item' && $asin)
                                <a
                                    href="{{ route('admin.selling.adsItems.details', [
                                        'asin' => $asin,
                                        'period' => request('period', '1d'),
                                    ]) }}">
                                    <i class="bx bx-left-arrow-alt me-1"></i>
                                    Back to Selling Ads Item Dashboard
                                </a>
                            @else
                                <a
                                    href="{{ route('admin.ads.overview.index', [
                                        'period' => request('period', '7d'),
                                    ]) }}">
                                    <i class="bx bx-left-arrow-alt me-1"></i>
                                    Back to Ads Overview Dashboard
                                </a>
                            @endif
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        {{-- LEFT SIDE FILTER GROUP --}}
                        <div class="col-lg-12">
                            <form method="GET" action="{{ route('admin.ads.overview.campaignOverview') }}" id="filterForm"
                                class="row g-2">
                                {{-- Search --}}
                                <x-elements.search-box />
                                {{-- Country --}}
                                <x-elements.country-select :countries="['us' => 'US', 'ca' => 'CA']" />
                                {{-- Campaign --}}
                                <x-elements.campaign-select :campaigns="['SP' => 'SP', 'SB' => 'SB', 'SD' => 'SD']" />
                                {{-- Preserve multi-ASIN filter --}}
                                @php
                                    $asins = (array) request('asins', []);
                                @endphp
                                <input type="hidden" name="acos" value="{{ request('acos') }}">
                                @foreach ($asins as $asin)
                                    <input type="hidden" name="asins[]" value="{{ $asin }}">
                                @endforeach
                                <div class="col-6 col-md-auto">
                                    <div class="form-floating">
                                        <select class="form-select custom-dropdown-small" name="period"
                                            onchange="document.getElementById('filterForm').submit()">
                                            @php $selectedPeriod = request('period', 'all'); @endphp
                                            <option value="all" {{ $selectedPeriod === 'all' ? 'selected' : '' }}>All
                                            </option>
                                            <option value="1d" {{ $selectedPeriod === '1d' ? 'selected' : '' }}>
                                                Yesterday (1d)</option>
                                            <option value="7d" {{ $selectedPeriod === '7d' ? 'selected' : '' }}>7 Days
                                            </option>
                                            <option value="14d" {{ $selectedPeriod === '14d' ? 'selected' : '' }}>14 Days
                                            </option>
                                            <option value="30d" {{ $selectedPeriod === '30d' ? 'selected' : '' }}>30 Days
                                            </option>
                                        </select>
                                        <label for="period">Period</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-auto">
                                    <div class="form-floating">
                                        <select class="form-select custom-dropdown-small" name="acos"
                                            onchange="document.getElementById('filterForm').submit()">
                                            @php $selectedAcos = request('acos', 'all'); @endphp
                                            <option value="all" {{ $selectedAcos === 'all' ? 'selected' : '' }}>All
                                            </option>

                                            <option value="30" {{ $selectedAcos === '30' ? 'selected' : '' }}>ACOS <=
                                                    30, spend> 0
                                            </option>
                                            <option value="31" {{ $selectedAcos === '31' ? 'selected' : '' }}>ACOS >
                                                30, spend > 0
                                            </option>
                                            <option value="0" {{ $selectedAcos === '0' ? 'selected' : '' }}>
                                                ACOS = 0 , spend > 0</option>
                                            <option value="none" {{ $selectedAcos === 'none' ? 'selected' : '' }}>
                                                ACOS = 0 , spend = 0</option>
                                        </select>
                                        <label for="period">ACoS</label>
                                    </div>
                                </div>
                                <div class="col-6 col-md-auto">
                                    <div class="form-floating">
                                        <select class="form-select custom-dropdown-small" name="sp_targeting_type"
                                            onchange="document.getElementById('filterForm').submit()">
                                            @php $selectedTargetType = request('sp_targeting_type', 'all'); @endphp
                                            <option value="all" {{ $selectedTargetType === 'all' ? 'selected' : '' }}>
                                                All
                                            </option>
                                            <option value="AUTO" {{ $selectedTargetType === 'AUTO' ? 'selected' : '' }}>
                                                AUTO</option>
                                            <option value="MANUAL"
                                                {{ $selectedTargetType === 'MANUAL' ? 'selected' : '' }}>MANUAL
                                            </option>

                                        </select>
                                        <label for="period">Targeting Type</label>
                                    </div>
                                </div>

                                <div class="col-6 col-md-auto">
                                    <div class="form-floating">
                                        @php
                                            $subdayTime = now(config('timezone.market'))->subDay()->toDateString();
                                        @endphp
                                        <input class="form-control" type="date" name="date"
                                            value="{{ request('date', $subdayTime) }}" max="{{ $subdayTime }}"
                                            onchange="document.getElementById('filterForm').submit()"
                                            onclick="this.showPicker()">
                                        <label for="date">Report Date</label>
                                    </div>
                                </div>
                                {{-- RIGHT: 3-dots menu only (right on lg, normal on mobile) --}}
                                <div class="col-6 col-lg-auto ms-lg-auto d-flex justify-content-end">
                                    <div class="dropdown">
                                        <button
                                            class="btn btn-light w-100 d-flex align-items-center justify-content-center menuBtn"
                                            type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="mdi mdi-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end mt-2"
                                            aria-labelledby="dropdownMenuButton1"
                                            style="--bs-dropdown-item-padding-y: 0.5rem;">
                                            <small class="text-muted fw-semibold ms-3">More Actions</small>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center" href="#"
                                                    data-bs-toggle="modal" data-bs-target="#columnFilterPopupModal">
                                                    <i class="bx bx-columns font-size-18 text-primary me-1"></i>
                                                    Show / Hide Columns
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @php
                        $period = request('period', 'all');

                        // Map each period to its fields & cell class
                        $periodConfig = [
                            '1d' => [
                                'label' => '1d',
                                'spend_field' => 'total_spend',
                                'sales_field' => 'total_sales',
                                'purchases_field' => 'purchases7d',
                                'acos_field' => 'acos',
                                'cell_class' => 'table-light',
                            ],
                            '7d' => [
                                'label' => '7d',
                                'spend_field' => 'total_spend_7d',
                                'sales_field' => 'total_sales_7d',
                                'purchases_field' => 'purchases7d_7d',
                                'acos_field' => 'acos_7d',
                                'cell_class' => 'table-warning',
                            ],
                            '14d' => [
                                'label' => '14d',
                                'spend_field' => 'total_spend_14d',
                                'sales_field' => 'total_sales_14d',
                                'purchases_field' => 'purchases7d_14d',
                                'acos_field' => 'acos_14d',
                                'cell_class' => 'table-success',
                            ],
                            '30d' => [
                                'label' => '30d',
                                'spend_field' => 'total_spend_30d',
                                'sales_field' => 'total_sales_30d',
                                'purchases_field' => 'purchases7d_30d',
                                'acos_field' => 'acos_30d',
                                'cell_class' => 'table-info',
                            ],
                        ];

                        // Which periods should be shown?
                        $activePeriods =
                            $period === 'all'
                                ? array_keys($periodConfig) // show all 1d/7d/14d/30d
                                : (isset($periodConfig[$period])
                                    ? [$period] // show only selected period
                                    : ['1d']); // fallback
                    @endphp

                    {{--  Column Visibility --}}
                    <x-elements.column-visibility :columns="[
                        'campaign_id' => 'Campaign ID',
                        'date' => 'Date',
                        'asin' => 'ASIN',
                        'country' => 'Country',
                        'campaign_type' => 'Campaign Type',
                        'group' => 'Group',
                        'suggested_bid' => 'Suggested Budget',
                        'ai_recommendation' => 'Ai Recommendation ✨',
                    ]" :default-visible="['country', 'campaign_type', 'asin', 'ai_recommendation']" />
                    {{--  Column Visibility --}}

                    <div class="table-responsive custom-sticky-wrapper">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 custom-sticky-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th data-col="campaign_id">Campaign Id</th>
                                    <th>Campaign Name</th>
                                    <th data-col="asin">ASIN</th>
                                    <th data-col="campaign_type">Type</th>
                                    <th>Targeting Type</th>
                                    <th data-col="date">Report Date</th>
                                    <th data-col="country">Country</th>
                                    <th data-col="group">Group</th>

                                    @foreach ($activePeriods as $pKey)
                                        @php
                                            $cfg = $periodConfig[$pKey];
                                            $isActive = request('sort_acos') === $cfg['acos_field'];
                                            $isDesc = request('sort_direction') === 'desc';
                                            $sortClass = $isActive
                                                ? ($isDesc
                                                    ? 'sorting_desc currently-sorted'
                                                    : 'sorting_asc currently-sorted')
                                                : 'sorting';
                                            $params = array_merge(
                                                request()->except(['sort_acos', 'sort_direction', 'page']),
                                                [
                                                    'sort_acos' => $cfg['acos_field'],
                                                    'sort_direction' => $isActive && !$isDesc ? 'desc' : 'asc',
                                                ],
                                            );
                                            $url = url()->current() . '?' . http_build_query($params);
                                        @endphp
                                        <th>Spend {{ $cfg['label'] }}</th>
                                        <th>Sales {{ $cfg['label'] }}</th>
                                        <th>Purchase {{ $cfg['label'] }}</th>
                                        <th colspan="1" class="{{ $sortClass }} pointer" onclick="window.location='{{ $url }}'">
                                            <div class="select-none d-flex align-items-center">
                                                ACoS {{ $cfg['label'] }}
                                                <span class="ms-1">
                                                    <i class="mdi mdi-arrow-up-bold{{ $isActive && !$isDesc ? ' text-dark' : ' text-muted' }}"></i>
                                                    <i class="mdi mdi-arrow-down-bold{{ $isActive && $isDesc ? ' text-dark' : ' text-muted' }}"></i>
                                                </span>
                                            </div>
                                        </th>
                                    @endforeach
                                    <th>Status</th>
                                    <th>Daily Budget</th>
                                    <th class="wide-col" data-col="ai_recommendation">
                                        <span class="ai-gradient-text">AI Suggested Budget ✨
                                        </span>
                                    </th>
                                    <th data-col="suggested_bid">Suggested Budget</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($campaigns->count() > 0)
                                    @foreach ($campaigns as $index => $campaign)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td data-col="campaign_id"><livewire:ads.overview-campaign-keywords
                                                    :campaign-id="$campaign->campaign_id" :key="'campaign-keywords-' . $campaign->campaign_id" /></td>
                                            <td class="ellipsis-text" title="{{ $campaign->campaign_name ?? '' }}">
                                                {{ $campaign->campaign_name ?? 'N/A' }}</td>
                                            @php
                                                $relatedAsins = is_string($campaign->related_asins)
                                                    ? json_decode($campaign->related_asins, true)
                                                    : $campaign->related_asins;
                                            @endphp
                                            <td style="white-space: pre-line;" data-col="asin">
                                                @if (!empty($relatedAsins))
                                                    {{ implode("\n", (array) $relatedAsins) }}@else{{ $campaign->asin ?? 'N/A' }}
                                                @endif
                                            </td>
                                            <td data-col="campaign_type">{{ $campaign->campaign_types ?? 'N/A' }}</td>
                                            <td>{{ $campaign->sp_targeting_type ?? '--' }}</td>
                                            <td data-col="date">{{ $campaign->report_week ?? 'N/A' }}</td>
                                            <td data-col="country">{{ $campaign->country ?? 'N/A' }}</td>

                                            @php
                                                $from = (int) ($campaign->from_group ?? 0);
                                                $to = (int) ($campaign->to_group ?? 0);

                                                $badgeClass = fn(int $g) => match ($g) {
                                                    1 => 'success',
                                                    2 => 'warning',
                                                    3, 4 => 'danger',
                                                    default => 'secondary',
                                                };

                                                $label = fn(int $g) => "Group {$g}";
                                            @endphp

                                            <td data-col="group">
                                                @if ($to <= 0)
                                                    <span class="text-muted">-</span>
                                                @elseif($from > 0 && $from !== $to)
                                                    <span
                                                        class="badge badge-soft-{{ $badgeClass($from) }}">{{ $label($from) }}</span>
                                                    <span class="text-muted"><i class="bx bx-right-arrow-alt"></i></span>
                                                    <span
                                                        class="badge badge-soft-{{ $badgeClass($to) }}">{{ $label($to) }}</span>
                                                @else
                                                    <span
                                                        class="badge badge-soft-{{ $badgeClass($to) }}">{{ $label($to) }}</span>
                                                @endif
                                            </td>

                                            @foreach ($activePeriods as $pKey)
                                                @php
                                                    $cfg = $periodConfig[$pKey];
                                                    $cellClass = $cfg['cell_class'];

                                                    $spend = data_get($campaign, $cfg['spend_field']);
                                                    $sales = data_get($campaign, $cfg['sales_field']);
                                                    $purchases = data_get($campaign, $cfg['purchases_field']);
                                                    $acos = data_get($campaign, $cfg['acos_field']);
                                                @endphp

                                                <td class="{{ $cellClass }}">
                                                    {{ !is_null($spend) ? '$' . number_format($spend, 2) : 'N/A' }}
                                                </td>
                                                <td class="{{ $cellClass }}">
                                                    {{ !is_null($sales) ? '$' . number_format($sales, 2) : 'N/A' }}
                                                </td>
                                                <td class="{{ $cellClass }}">
                                                    {{ !is_null($purchases) ? $purchases : 'N/A' }}
                                                </td>
                                                <td class="{{ $cellClass }}">
                                                    {{ !is_null($acos) ? $acos . '%' : 'N/A' }}
                                                </td>
                                            @endforeach

                                            <td>
                                                @php
                                                    $state =
                                                        $campaign->sp_campaign_state ??
                                                        ($campaign->sb_campaign_state ?? null);
                                                @endphp

                                                @if ($state === 'ENABLED')
                                                    <span class="badge bg-success">{{ $state }}</span>
                                                @elseif ($state === 'PAUSED')
                                                    <span class="badge bg-warning">{{ $state }}</span>
                                                @elseif ($state)
                                                    <span class="badge bg-danger">{{ $state }}</span>
                                                @else
                                                    <span class="badge bg-secondary">N/A</span>
                                                @endif
                                            </td>

                                            <td class="table-success">
                                                @if (!is_null($campaign->old_budget) && $campaign->total_daily_budget != $campaign->old_budget)
                                                    <span class="text-decoration-line-through text-danger">
                                                        ${{ $campaign->old_budget }}
                                                    </span>
                                                    <span class="fw-bold text-success">
                                                        ${{ $campaign->total_daily_budget }}
                                                    </span>
                                                @else
                                                    ${{ number_format($campaign->total_daily_budget, 2) ?? 'N/A' }}
                                                @endif
                                            </td>
                                            <livewire:ads.campaign-ai-recommendation :campaign-id="$campaign->id" column="budget"
                                                :wire:key="'budget-'.$campaign->id" />
                                            <td data-col="suggested_bid">
                                                @if (is_numeric($campaign->suggested_budget))
                                                    ${{ number_format($campaign->suggested_budget, 2) }}
                                                @else
                                                    {{ $campaign->suggested_budget ?? '--' }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="100%" class="text-center">No data item available for this</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <!-- end table -->
                    </div>
                    <div class="mt-2">
                        {{ $campaigns->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                    </div>
                    <!-- end table responsive -->
                    <p class="text-muted mb-0">
                        <span class="badge badge-soft-info">Note :</span> 7d / 14d are calculated from yesterday’s date to
                        the past 7 or 14 days based on the date picker.
                    </p>
                    <p class="text-muted mb-0">
                        <span class="badge badge-soft-info">Note :</span> Campaign groups are calculated daily based on
                        ACOS, spend, and sales:
                        <span class="ms-1">
                            <span class="badge badge-soft-success">Group 1 (ACOS &lt; 30)</span>
                            <span class="badge badge-soft-warning ms-1">Group 2 (ACOS &gt; 30)</span>
                            <span class="badge badge-soft-danger ms-1">Group 3 (Spend &gt; 0 &amp; ACOS = 0)</span>
                            <span class="badge badge-soft-danger ms-1">Group 4 (No Spend &amp; No Sales)</span>
                        </span>
                    </p>
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->
@endsection
