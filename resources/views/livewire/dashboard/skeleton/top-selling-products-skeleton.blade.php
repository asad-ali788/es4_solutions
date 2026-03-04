<div class="col-12 col-xl-4 d-flex">
    <div class="card w-100 mb-0">
        <div class="card-body">

            <!-- Header -->
            <div class="d-flex align-items-center mb-2">
                <div class="avatar-xs me-2">
                    <div class="skel skel-avatar skel-shimmer"></div>
                </div>
                <div class="flex-grow-1">
                    <div class="skel skel-md skel-shimmer w-75"></div>
                </div>
            </div>

            <!-- Table Skeleton -->
            <div class="table-responsive" style="max-height: 325px;">
                <table class="table table-sm align-middle mb-0">
                    <tbody>

                        @for ($i = 0; $i < 6; $i++)
                            <tr style="line-height: 1.2;">
                                <!-- Rank -->
                                <td style="width: 30px;">
                                    <div class="skel skel-sm skel-shimmer w-75"></div>
                                </td>

                                <!-- ASIN + Date -->
                                <td style="width: 50%;">
                                    <div class="skel skel-sm skel-shimmer w-75 mb-1"></div>
                                    <div class="skel skel-sm skel-shimmer w-50"></div>
                                </td>

                                <!-- Units + Revenue -->
                                <td class="text-end p-1 pe-2" style="width: 50%;">
                                    <div class="skel skel-sm skel-shimmer w-50 ms-auto mb-1"></div>
                                    <div class="skel skel-md skel-shimmer w-40 ms-auto mb-1"></div>
                                    <div class="skel skel-sm skel-shimmer w-45 ms-auto"></div>
                                </td>
                            </tr>
                        @endfor

                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
