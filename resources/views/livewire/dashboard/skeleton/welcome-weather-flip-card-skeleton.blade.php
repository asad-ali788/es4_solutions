<div class="card w-100 mb-0">
    {{-- 👇 lock height to real weather card --}}
    <div class="card-body d-flex flex-column" style="min-height: 240px;">

        {{-- Header (matches real header height) --}}
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="skel skel-sm skel-shimmer" style="width:140px;"></div>

            <div class="d-flex align-items-center gap-2">
                <div class="skel skel-sm skel-shimmer" style="width:180px;height:30px;"></div>
                <div class="skel skel-sm skel-shimmer" style="width:90px;"></div>
            </div>
        </div>

        {{-- Divider (same as real <hr>) --}}
        <div class="skel skel-shimmer mb-2" style="height:1px;width:100%;opacity:.25;"></div>

        {{-- Weather strip area (fixed height, prevents jump) --}}
        <div class="d-flex gap-2 align-items-start flex-grow-1"
             style="min-height: 150px;">

            @for ($i = 0; $i < 5; $i++)
                <div class="flex-grow-1 text-center">

                    {{-- Day label --}}
                    <div class="skel skel-sm skel-shimmer mx-auto mb-2"
                         style="width:70px;"></div>

                    {{-- Icon (same visual size as real icon) --}}
                    <div class="skel skel-sm skel-shimmer mx-auto mb-2"
                         style="width:22px;height:22px;border-radius:50%;"></div>

                    {{-- Temp --}}
                    <div class="skel skel-sm skel-shimmer mx-auto mb-1"
                         style="width:60px;"></div>

                    {{-- Condition --}}
                    <div class="skel skel-sm skel-shimmer mx-auto"
                         style="width:80px;"></div>
                </div>
            @endfor

        </div>
    </div>
</div>
