<tr @if ($forecast->status_flag === 'pending') wire:poll.3s="refreshRow" @endif>
    <td>
        @if ($forecast->status_flag === 'ready')
            <a class="text-success fw-bold" href="{{ route('admin.orderforecastasin.show', $forecast->id) }}">
                {{ $forecast->order_name }}
            </a>
        @else
            <span class="text-warning fw-bold d-flex align-items-center">
                <i class="bx bx-hourglass bx-spin font-size-16 align-middle me-2"></i>
                {{ $forecast->order_name }}
            </span>
        @endif
    </td>

    <td>{{ $forecast->order_date }}</td>
    <td>{{ $forecast->notes }}</td>

    <td>
        @php
            $statusClasses = [
                'draft' => 'primary',
                'finalized' => 'success',
                'archived' => 'danger',
            ];
            $badge = $statusClasses[$forecast->status] ?? 'dark';
        @endphp

        <span class="badge bg-{{ $badge }}">
            {{ ucfirst($forecast->status) }}
        </span>
    </td>

    <td>
        <div class="dropdown">
            <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                <i class="mdi mdi-dots-horizontal font-size-18"></i>
            </a>

            <ul class="dropdown-menu dropdown-menu-end">
                @can('order_forecast.update')
                    @if ($forecast->status !== 'finalized')
                        <li>
                            <a href="{{ route('admin.orderforecast.edit', $forecast->id) }}" class="dropdown-item">
                                <i class="mdi mdi-pencil font-size-16 text-primary me-1"></i>
                                Edit
                            </a>
                        </li>
                    @endif
                @endcan

                @can('order_forecast.download-snapshots')
                    <li>
                        <a href="{{ route('admin.orderforecast.downloadForecastSnapshots', ['id' => $forecast->id]) }}"
                            class="dropdown-item">
                            <i class="bx bx-download font-size-16 text-primary me-1"></i>
                            Download Snapshots
                        </a>
                    </li>
                @endcan

                @can('order_forecast.delete')
                    <li>
                        <form action="{{ route('admin.orderforecast.destroy', $forecast->id) }}" method="POST"
                            onsubmit="return confirm('Are you sure you want to delete {{ $forecast->order_name }}?');">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="dropdown-item text-danger">
                                <i class="mdi mdi-trash-can font-size-16 text-danger me-1"></i>
                                Delete
                            </button>
                        </form>
                    </li>
                @endcan
            </ul>
        </div>
    </td>
</tr>
