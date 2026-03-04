 <div class="col-12">
     <div class="card w-100 mb-0">
         <div class="card-body">

             <div class="d-flex mb-2 justify-content-between gap-2 flex-wrap">
                 <div class="d-flex align-items-center">
                     <div class="me-3 skel skel-avatar skel-shimmer"></div>
                     <div class="skel skel-md skel-shimmer" style="width: 260px;"></div>
                 </div>
             </div>

             {{-- ✅ IMPORTANT: remove gap-3 (keep only g-3) --}}
             <div class="row g-3 align-items-stretch">

                 {{-- TY --}}
                 <div class="col-12 col-sm-6 col-lg-4 align-self-center" style="min-width:0;">
                     <div class="d-flex align-items-center gap-2 mb-2">
                         <div class="skel skel-sm skel-shimmer" style="width:10px;height:10px;border-radius:999px;">
                         </div>
                         <div class="skel skel-sm skel-shimmer" style="width:220px;"></div>
                     </div>

                     <div class="skel skel-lg skel-shimmer mb-2" style="width:120px;"></div>
                     <div class="skel skel-sm skel-shimmer mb-3" style="width:220px;"></div>

                     <div class="skel skel-sm skel-shimmer" style="height:8px;width:85%;border-radius:999px;"></div>
                 </div>

                 {{-- LY --}}
                 <div class="col-12 col-sm-6 col-lg-4 align-self-center" style="min-width:0;">
                     <div class="d-flex align-items-center gap-2 mb-2">
                         <div class="skel skel-sm skel-shimmer" style="width:10px;height:10px;border-radius:999px;">
                         </div>
                         <div class="skel skel-sm skel-shimmer" style="width:220px;"></div>
                     </div>

                     <div class="skel skel-lg skel-shimmer mb-2" style="width:120px;"></div>
                     <div class="skel skel-sm skel-shimmer" style="width:220px;"></div>
                 </div>

                 {{-- ✅ Table column must be col-lg-4 (not "col") --}}
                 <div class="col-12 col-lg-4" style="min-width:0;">
                     <div class="table-responsive-sm h-100" style="overflow-x:hidden;">
                         <table class="table align-middle table-nowrap mb-0 w-100">
                             <tbody>
                                 @for ($i = 0; $i < 2; $i++)
                                     <tr>
                                         <td style="width: 50%;">
                                             <div class="skel skel-sm skel-shimmer" style="width: 70px;">
                                             </div>
                                         </td>
                                         <td class="text-end">
                                             <div class="skel skel-md skel-shimmer mb-2"
                                                 style="width: 90px; margin-left:auto;"></div>
                                             <div class="skel skel-sm skel-shimmer"
                                                 style="width: 140px; margin-left:auto;"></div>
                                         </td>
                                     </tr>
                                 @endfor
                             </tbody>
                         </table>
                     </div>
                 </div>

             </div>

         </div>
     </div>
 </div>
