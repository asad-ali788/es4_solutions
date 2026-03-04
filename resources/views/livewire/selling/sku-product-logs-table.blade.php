<div class="card border-2 border-primary">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="h4 card-title mb-0">Product Logs</div>
            </div>
            <div class="d-flex gap-2 mb-3">

                <!-- Field Filter -->
                <select wire:model.live="fieldFilter" class="form-select">
                    <option value="">All Fields</option>
                    @foreach ($fieldNames as $field)
                        <option value="{{ $field }}">{{ ucwords(str_replace('_', ' ', $field)) }}</option>
                    @endforeach
                </select>

                <!-- Market Filter -->
                <select wire:model.live="marketFilter" class="form-select">
                    <option value="">All Markets</option>
                    @foreach (['US' => 'United States', 'UK' => 'United Kingdom', 'CA' => 'Canada', 'FR' => 'France', 'DE' => 'Germany', 'ES' => 'Spain'] as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>

            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Field Name</th>
                        <th>Old Value</th>
                        <th>New Value</th>
                        <th>Market</th>
                        <th>User</th>
                        <th>Updated At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $index => $log)
                        <tr>
                            <th scope="row">{{ $logs->firstItem() + $index }}</th>
                            <td>{{ $log->field_name ?? 'N/A' }}</td>
                            <td>{{ $log->old_value ?? 'N/A' }}</td>
                            <td>{{ $log->new_value ?? 'N/A' }}</td>
                            <td>{{ $log->country ?? 'N/A' }}</td>
                            <td>{{ $log->user->name ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($log->updated_at)->format('jS M Y, g:i A') }}</td>
                        </tr>
                    @empty
                        <tr class="text-center">
                            <td colspan="7" class="text-danger fw-bold">No logs found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-2">
                {{ $logs->onEachSide(1)->links(data: ['scrollTo' => false]) }}
            </div>
        </div>
    </div>
</div>
