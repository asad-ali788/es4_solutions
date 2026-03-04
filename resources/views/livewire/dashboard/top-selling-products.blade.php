 <div class="col-12 col-xl-4 d-flex">
     <div class="card w-100 mb-0">
         <div class="card-body">
             <div class="d-flex align-items-center mb-2">
                 <div class="avatar-xs me-2">
                     <span class="avatar-title rounded-circle glance glance-success font-size-18">
                         <i class="bx bx-bar-chart-alt-2"></i>
                     </span>
                 </div>
                 <h5 class="font-size-14 mb-0">
                     Yesterday's Top 10 Selling Product
                 </h5>
             </div>
             <div class="table-responsive" data-simplebar="init" style="max-height: 325px;">
                 <table class="table table-sm align-middle mb-0">
                     <tbody>
                         @if (!empty($topSelling))
                             @foreach ($topSelling as $item)
                                 <tr style="line-height: 1.2;">
                                     <td>#{{ $loop->iteration }}</td>
                                     <td style="width: 50%;">
                                         <h6 class="mb-0 text-truncate" style="font-size: 12px;">
                                             {{ $item['asin'] ?? 'N/A' }}
                                         </h6>
                                         <small class="text-muted">
                                             {{ \Carbon\Carbon::parse($item['sale_date'])->format('M d, Y') }}
                                         </small>
                                     </td>
                                     <td class="text-end p-1 pe-2" style="width: 50%;">
                                         <small class="text-muted">Units Sold</small>
                                         <h5 style="font-size: 14px; font-weight: 500;">
                                             {{ $item['total_units'] ?? 0 }}
                                         </h5>
                                         <small class="text-muted d-block">
                                             ${{ number_format($item['total_revenue'], 2) ?? 'N/A' }}
                                         </small>
                                     </td>
                                 </tr>
                             @endforeach
                         @else
                             <tr>
                                 <td colspan="3" class="text-center text-muted small p-3">
                                     <div class="d-flex flex-column align-items-center justify-content-center">
                                         <div class="w-50 opacity-50" style="filter: grayscale(50%) blur(.5px);">
                                             <img src="{{ asset('assets/images/empty-folder.png') }}" alt="No data"
                                                 class="img-fluid" style="max-width: 120px;">
                                         </div>
                                         <div class="mb-2">No data available now</div>
                                     </div>
                                 </td>
                             </tr>
                         @endif
                     </tbody>
                 </table>
             </div>
         </div>
     </div>
 </div>
