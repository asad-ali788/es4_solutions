 @extends('layouts.app')
 @section('content')
     <!-- start page title -->
     <div class="row">
         <div class="col-12">
             <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                 <h4 class="mb-sm-0 font-size-18">Amazon ADS - Campaigns Keywords - <span class="text-primary">
                         {{ $id ?? 'N/A' }}</span></h4>
                 <div class="page-title-right">
                     <ol class="breadcrumb m-0">
                         <li class="breadcrumb-item active">
                             @if ($type == 'SP')
                                 <a href="{{ route('admin.ads.campaigns') }}">
                                     <i class="bx bx-left-arrow-alt me-1"></i> Back to Campaigns SP
                                 </a>
                             @elseif ($type == 'SB')
                                 <a href="{{ route('admin.ads.campaignsSb') }}">
                                     <i class="bx bx-left-arrow-alt me-1"></i> Back to Campaigns SB
                                 </a>
                             @elseif ($type == 'SD')
                                 <a href="{{ route('admin.ads.campaignsSd') }}">
                                     <i class="bx bx-left-arrow-alt me-1"></i> Back to Campaigns SD
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
                 <ul role="tablist" class="nav-tabs nav-tabs-custom pt-2 nav">
                     <li class="nav-item">
                         <a href="#" class="nav-link {{ request()->is('admin/ads/campaigns*') ? 'active' : '' }}">
                             Campaigns {{ $type ?? 'N/A' }}
                         </a>
                     </li>
                 </ul>
                 <div class="card-body pt-2">
                     <div class="row mb-2">
                         <div class="col-sm-4">
                             <form method="GET" action="{{ route('admin.ads.campaignKeywords', [$id, $type]) }}"
                                 class="d-inline-block me-2 mb-2">
                                 <!-- Search -->
                                 <x-elements.search-box />
                             </form>
                         </div>
                     </div>

                     <div class="table-responsive">
                         <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                             <thead class="table-light">
                                 <tr>
                                     <th>#</th>
                                     @if ($type == 'SD')
                                         <th>Region</th>
                                         <th>Target ID</th>
                                         <th>Expression Type</th>
                                         <th>Expression</th>
                                     @elseif($spTarget)
                                         <th>Country</th>
                                         <th>Target ID</th>
                                         <th>Ad Group ID</th>
                                         <th>Expression</th>
                                     @else
                                         <th>Country</th>
                                         <th>Keyword ID</th>
                                         <th>Ad Group ID</th>
                                         <th>Keyword</th>
                                         <th>Match Type</th>
                                     @endif
                                     <th>State</th>
                                     <th>Bid</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 @if (isset($keywords) && count($keywords) > 0)
                                     @foreach ($keywords as $index => $keyword)
                                         <tr>
                                             <td>{{ $loop->iteration }}</td>
                                             @if ($type == 'SD')
                                                 <td>{{ $keyword->region ?? 'N/A' }}</td>
                                                 <td>{{ $keyword->target_id ?? 'N/A' }}</td>
                                                 <td>{{ $keyword->expression_type ?? 'N/A' }}</td>
                                                 <td>
                                                     @if (is_array($keyword->expression))
                                                         @foreach ($keyword->expression as $expr)
                                                             <div>
                                                                 Type: <strong>{{ $expr['type'] ?? 'N/A' }}</strong>,
                                                                 Value: <strong>{{ $expr['value'] ?? 'N/A' }}</strong>
                                                             </div>
                                                         @endforeach
                                                     @else
                                                         {{ $keyword->expression ?? 'N/A' }}
                                                     @endif
                                                 </td>
                                             @elseif($spTarget)
                                                 <td>{{ $keyword->country ?? 'N/A' }}</td>
                                                 <td>{{ $keyword->target_id ?? 'N/A' }}</td>
                                                 <td>{{ $keyword->ad_group_id ?? 'N/A' }}</td>
                                                 <td>
                                                     @if (is_array($keyword->expression))
                                                         @foreach ($keyword->expression as $expr)
                                                             <div>
                                                                 Type: <strong>{{ $expr['type'] ?? 'N/A' }}</strong>,
                                                                 Value: <strong>{{ $expr['value'] ?? 'N/A' }}</strong>
                                                             </div>
                                                         @endforeach
                                                     @else
                                                         {{ $keyword->expression ?? 'N/A' }}
                                                     @endif
                                                 </td>
                                             @else
                                                 <td>{{ $keyword->country ?? 'N/A' }}</td>
                                                 <td>{{ $keyword->keyword_id ?? 'N/A' }}</td>
                                                 <td>{{ $keyword->ad_group_id ?? 'N/A' }}</td>
                                                 <td>{{ $keyword->keyword_text ?? 'N/A' }}</td>
                                                 <td>{{ $keyword->match_type ?? 'N/A' }}</td>
                                             @endif
                                             <td>
                                                 @php
                                                     $state = strtoupper($keyword->state);
                                                 @endphp
                                                 @if ($state === 'ENABLED')
                                                     <span class="badge bg-success">Enabled</span>
                                                 @elseif ($state === 'PAUSED')
                                                     <span class="badge bg-warning">Paused</span>
                                                 @elseif ($state === 'ARCHIVED')
                                                     <span class="badge bg-danger">Archived</span>
                                                 @endif
                                             </td>
                                             <td>{{ $keyword->bid ?? 'N/A' }}</td>
                                         </tr>
                                     @endforeach
                                 @else
                                     <tr>
                                         <td colspan="100%" class="text-center">No keywords available</td>
                                     </tr>
                                 @endif
                             </tbody>
                         </table>

                         <div class="mt-2">
                             @if ($keywords)
                                 {{ $keywords->appends(request()->query())->links('pagination::bootstrap-5') }}
                             @endif
                         </div>
                     </div>
                     <!-- end table responsive -->
                 </div>
                 <!-- end card body -->
             </div>
             <!-- end card -->
         </div>
         <!-- end col -->
     </div>
     <!-- end row -->
 @endsection
