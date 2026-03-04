<div class="col-12 col-lg-6 d-flex">
    <div class="card w-100 mb-0">
        <div class="card-body">

            <!-- Header -->
            <div class="d-flex align-items-center mb-2">
                <div class="avatar-xs me-2">
                    <div class="skel skel-avatar skel-shimmer"></div>
                </div>

                <div class="flex-grow-1">
                    <div class="skel skel-md skel-shimmer w-75 mb-1"></div>
                </div>
            </div>

            <div class="skel skel-sm skel-shimmer w-75 mb-3"></div>

            <!-- Table Skeleton -->
            <div class="table-responsive mt-2">
                <table class="table align-middle mb-0">
                    <tbody>

                        @for ($i = 0; $i < 6; $i++)
                            <tr>
                                <!-- Campaign Name -->
                                <td>
                                    <div class="skel skel-sm skel-shimmer w-50 mb-1"></div>
                                    <div class="skel skel-sm skel-shimmer w-75"></div>
                                </td>

                                <!-- Sales -->
                                <td>
                                    <div class="skel skel-sm skel-shimmer w-50 mb-1"></div>
                                    <div class="skel skel-md skel-shimmer w-60"></div>
                                </td>

                                <!-- Spend -->
                                <td>
                                    <div class="skel skel-sm skel-shimmer w-50 mb-1"></div>
                                    <div class="skel skel-md skel-shimmer w-60"></div>
                                </td>
                            </tr>
                        @endfor

                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
