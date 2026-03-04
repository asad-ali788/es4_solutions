<div class="row g-3 mt-2 align-items-stretch">
    @for ($c = 0; $c < 2; $c++)
        <div class="col-12 col-md-6 d-flex">
            <div class="card h-100 w-100">
                <div class="card-body">

                    {{-- Header --}}
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-2 skel skel-avatar skel-shimmer"></div>

                        <div class="flex-grow-1">
                            <div class="skel skel-md skel-shimmer" style="width: 75%;"></div>
                        </div>
                    </div>

                    {{-- Subtitle --}}
                    <div class="mb-3">
                        <div class="skel skel-sm skel-shimmer" style="width: 60%;"></div>
                    </div>

                    {{-- Table skeleton --}}
                    <div class="table-responsive" style="max-height:300px;">
                        <table class="table table-sm align-middle mb-0">
                            <tbody>
                                @for ($i = 0; $i < 7; $i++)
                                    <tr style="line-height:2.1;">
                                        {{-- Rank --}}
                                        <td class="p-1" style="width: 10%;">
                                            <div class="skel skel-sm skel-shimmer" style="width: 24px;"></div>
                                        </td>

                                        {{-- Campaign name --}}
                                        <td style="width:70%;">
                                            <div class="skel skel-md skel-shimmer mb-1" style="width: 100%;"></div>
                                            <div class="skel skel-sm skel-shimmer" style="width: 70%;"></div>
                                        </td>

                                        {{-- Metrics --}}
                                        <td class="text-end p-1 pe-2" style="width:30%;">
                                            <div class="skel skel-sm skel-shimmer mb-1"
                                                style="width: 45%; margin-left:auto;"></div>
                                            <div class="skel skel-md skel-shimmer"
                                                style="width: 65%; margin-left:auto;"></div>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    @endfor
</div>
