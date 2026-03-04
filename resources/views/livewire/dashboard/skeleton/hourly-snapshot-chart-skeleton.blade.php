<div class="card w-100 mb-0">
    <div class="card-body">
        {{-- Header (same style as table skeleton) --}}
        <div class="d-flex align-items-center mb-3">
            <div class="avatar-xs me-2">
                <div class="skel skel-avatar skel-shimmer"></div>
            </div>
            <div class="flex-grow-1">
                <div class="skel skel-md skel-shimmer w-50"></div>
            </div>
        </div>

        {{-- Legend (Today / Yesterday) --}}
        <div class="d-flex align-items-center gap-4 mb-3">
            <div class="d-flex align-items-center gap-2">
                <div class="skel skel-sm skel-shimmer" style="width:12px;height:12px;border-radius:50%;"></div>
                <div class="skel skel-sm skel-shimmer" style="width:60px;"></div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <div class="skel skel-sm skel-shimmer" style="width:12px;height:12px;border-radius:50%;"></div>
                <div class="skel skel-sm skel-shimmer" style="width:80px;"></div>
            </div>
        </div>

        {{-- Chart Skeleton --}}
        <div style="min-height:220px;">
            <div class="d-flex w-100" style="gap:12px;">

                {{-- Y-axis labels --}}
                <div class="d-flex flex-column justify-content-between"
                    style="width:28px; padding-top:10px; padding-bottom:34px;">
                    @for ($i = 0; $i < 6; $i++)
                        <div class="skel skel-sm skel-shimmer w-75"></div>
                    @endfor
                </div>

                {{-- Plot area --}}
                <div class="flex-grow-1 position-relative" style="min-height:200px;">

                    {{-- Grid lines --}}
                    <div class="position-absolute start-0 end-0 top-0 bottom-0" style="padding:10px 6px 40px;">
                        @for ($g = 0; $g < 6; $g++)
                            <div class="skel skel-shimmer mb-4" style="height:2px; width:100%; opacity:.25;"></div>
                        @endfor
                    </div>

                    {{-- Area fill hint --}}
                    <div class="position-absolute" style="left:10px; right:60px; top:90px;">
                        <div class="skel skel-shimmer" style="height:90px; border-radius:18px; opacity:.35;"></div>
                    </div>

                    {{-- Line 1 --}}
                    <div class="position-absolute" style="left:12px; right:120px; top:110px;">
                        <div class="skel skel-shimmer" style="height:6px; border-radius:999px;"></div>
                    </div>

                    {{-- Line 2 --}}
                    <div class="position-absolute" style="left:32px; right:12px; top:95px;">
                        <div class="skel skel-shimmer" style="height:6px; border-radius:999px;"></div>
                    </div>

                    {{-- X-axis labels --}}
                    <div class="position-absolute start-0 end-0 bottom-0" style="padding:0 6px 6px;">
                        <div class="d-flex justify-content-between">
                            @for ($x = 0; $x < 7; $x++)
                                <div class="skel skel-sm skel-shimmer" style="width:36px;"></div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
