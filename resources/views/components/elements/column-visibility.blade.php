@once
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                {{-- Header --}}
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Show / Hide Columns</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                {{-- Body --}}
                <div class="modal-body pt-2">
                    <div class="card jobs-categories mb-0">
                        <div class="card-body p-2 form-check">
                            @foreach ($columns as $key => $label)
                                <label class="px-3 py-2 rounded bg-light bg-opacity-50 d-block mb-2 cursor-pointer">
                                    {{ $label }}
                                    <input type="checkbox" class="form-check-input font-size-16 float-end mt-1"
                                        data-column-toggle data-col="{{ $key }}">
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                {{-- Footer --}}
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="button" class="btn btn-sm btn-primary" data-column-apply id="columnFilterPopupSubmit">
                        Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
@endonce

{{-- Defaults (optional, page-specific) --}}
@if (!empty($defaultVisible))
    @push('scripts')
        <script>
            window.VISIBLE_COLUMNS = @json(array_values($defaultVisible));
        </script>
    @endpush
@endif
