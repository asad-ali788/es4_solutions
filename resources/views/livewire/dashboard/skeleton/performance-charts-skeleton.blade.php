<div class="w-100">
    <div class="row g-3 mt-2 align-items-stretch w-100 mx-0">
        @php
            $bars = [70, 140, 95, 180, 110, 160, 85];
        @endphp

        @for ($c = 0; $c < 3; $c++)
            <div class="col-12 col-md-4 d-flex px-0 px-md-2">
                <div class="card w-100 mb-0 flex-fill">
                    <div class="card-body w-100">

                        {{-- Header --}}
                        <div class="d-flex align-items-center mb-2 w-100">
                            <div class="me-2 skel skel-avatar skel-shimmer"></div>

                            <div class="flex-grow-1 w-100">
                                <div class="skel skel-md skel-shimmer" style="width: 75%;"></div>
                            </div>
                        </div>

                        {{-- Subtitle --}}
                        <div class="mb-3 w-100">
                            <div class="skel skel-sm skel-shimmer" style="width: 85%;"></div>
                        </div>

                        {{-- Chart area --}}
                        <div class="skel-chart-wrap p-3 w-100" style="min-height:250px;">
                            <div class="d-flex align-items-end justify-content-between w-100"
                                style="gap:12px; min-height:215px;">
                                @for ($i = 0; $i < 7; $i++)
                                    <div class="flex-grow-1 d-flex flex-column justify-content-end">
                                        <div class="skel skel-shimmer"
                                            style="height: {{ $bars[$i] }}px; border-radius: 10px;"></div>
                                        <div class="skel skel-sm mt-2 skel-shimmer" style="width: 70%; margin: 0 auto;"></div>
                                    </div>
                                @endfor
                            </div>

                            {{-- Legend --}}
                            <div class="d-flex justify-content-center gap-3 mt-3 w-100">
                                <div class="skel skel-sm skel-shimmer" style="width: 70px;"></div>
                                <div class="skel skel-sm skel-shimmer" style="width: 70px;"></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>
