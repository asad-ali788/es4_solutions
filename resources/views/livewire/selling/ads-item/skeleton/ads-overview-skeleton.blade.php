<div class="row g-3 mb-3">

    {{-- SKELETON (while loading) --}}
    <div class="col-12">
        <div class="row g-3">

            {{-- LEFT CARD SKELETON --}}
            <div class="col-12 col-lg-6 d-flex">
                <div class="card w-100 mb-0">
                    <div class="card-body">

                        {{-- Header --}}
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="skel skel-md skel-shimmer" style="width: 55%;"></div>
                            <div class="skel skel-sm skel-shimmer" style="width: 20%;"></div>
                        </div>

                        {{-- Title + count --}}
                        <div class="mb-3">
                            <div class="skel skel-sm skel-shimmer" style="width: 35%;"></div>
                            <div class="d-flex align-items-end justify-content-between mt-2">
                                <div class="skel skel-lg skel-shimmer" style="width: 18%; height: 34px;"></div>
                                <div class="skel skel-sm skel-shimmer" style="width: 26%;"></div>
                            </div>
                        </div>

                        {{-- Right small ACoS lines --}}
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-end gap-3 mb-2">
                                <div class="skel skel-avatar skel-shimmer"
                                    style="width:10px;height:10px;border-radius:50%;"></div>
                                <div class="skel skel-sm skel-shimmer" style="width: 22%;"></div>
                                <div class="skel skel-sm skel-shimmer" style="width: 16%;"></div>
                            </div>
                            <div class="d-flex align-items-center justify-content-end gap-3">
                                <div class="skel skel-avatar skel-shimmer"
                                    style="width:10px;height:10px;border-radius:50%;"></div>
                                <div class="skel skel-sm skel-shimmer" style="width: 22%;"></div>
                                <div class="skel skel-sm skel-shimmer" style="width: 16%;"></div>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Bottom split: AUTO / MANUAL --}}
                        <div class="row g-0">
                            <div class="col-6 pe-3 border-end">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="skel skel-avatar skel-shimmer"
                                        style="width:12px;height:12px;border-radius:50%;"></div>
                                    <div class="skel skel-sm skel-shimmer" style="width: 30%;"></div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="skel skel-sm skel-shimmer" style="width: 60%;"></div>
                                        <div class="skel skel-md skel-shimmer mt-1" style="width: 40%;"></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="skel skel-sm skel-shimmer" style="width: 60%;"></div>
                                        <div class="skel skel-md skel-shimmer mt-1" style="width: 40%;"></div>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <div class="skel skel-sm skel-shimmer" style="width: 60%;"></div>
                                        <div class="skel skel-md skel-shimmer mt-1" style="width: 70%;"></div>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <div class="skel skel-sm skel-shimmer" style="width: 60%;"></div>
                                        <div class="skel skel-md skel-shimmer mt-1" style="width: 70%;"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6 ps-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="skel skel-avatar skel-shimmer"
                                        style="width:12px;height:12px;border-radius:50%;"></div>
                                    <div class="skel skel-sm skel-shimmer" style="width: 32%;"></div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="skel skel-sm skel-shimmer" style="width: 60%;"></div>
                                        <div class="skel skel-md skel-shimmer mt-1" style="width: 40%;"></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="skel skel-sm skel-shimmer" style="width: 60%;"></div>
                                        <div class="skel skel-md skel-shimmer mt-1" style="width: 40%;"></div>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <div class="skel skel-sm skel-shimmer" style="width: 60%;"></div>
                                        <div class="skel skel-md skel-shimmer mt-1" style="width: 70%;"></div>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <div class="skel skel-sm skel-shimmer" style="width: 60%;"></div>
                                        <div class="skel skel-md skel-shimmer mt-1" style="width: 70%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- RIGHT CARD SKELETON (tabs + 4 quadrants) --}}
            <div class="col-12 col-lg-6 d-flex">
                <div class="card w-100 mb-0">
                    <div class="card-body">

                        {{-- Tabs header --}}
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="skel skel-sm skel-shimmer" style="width: 14%; height: 20px;"></div>
                            <div class="skel skel-sm skel-shimmer" style="width: 14%; height: 20px;"></div>
                        </div>

                        <hr class="my-3">

                        {{-- 2x2 blocks --}}
                        <div class="row g-0">
                            @for ($i = 0; $i < 4; $i++)
                                <div
                                    class="col-6 {{ $i % 2 == 0 ? 'pe-3 border-end' : 'ps-3' }} {{ $i < 2 ? 'pb-3 border-bottom' : 'pt-3' }}">
                                    <div class="skel skel-sm skel-shimmer" style="width: 40%;"></div>

                                    <div class="row g-2 mt-2">
                                        <div class="col-6">
                                            <div class="skel skel-sm skel-shimmer" style="width: 70%;"></div>
                                            <div class="skel skel-md skel-shimmer mt-1" style="width: 35%;"></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="skel skel-sm skel-shimmer" style="width: 70%;"></div>
                                            <div class="skel skel-md skel-shimmer mt-1" style="width: 35%;"></div>
                                        </div>
                                        <div class="col-6 mt-2">
                                            <div class="skel skel-sm skel-shimmer" style="width: 70%;"></div>
                                            <div class="skel skel-md skel-shimmer mt-1" style="width: 60%;"></div>
                                        </div>
                                        <div class="col-6 mt-2">
                                            <div class="skel skel-sm skel-shimmer" style="width: 70%;"></div>
                                            <div class="skel skel-md skel-shimmer mt-1" style="width: 60%;"></div>
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
