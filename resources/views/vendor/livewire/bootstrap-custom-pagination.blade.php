@if ($paginator->hasPages())
    <div class="d-flex align-items-end justify-content-between flex-wrap gap-3">
        {{-- LEFT: Rows selector --}}
        <div style="min-width: 70px;">
            <label class="form-label mb-0 small text-muted">Rows</label>
            <select class="form-select form-select-sm" wire:model.live="perPage">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
        {{-- CENTER: Count --}}
        <div class="text-muted small mb-1">
            Showing
            {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}
            /
            {{ $paginator->total() }}
        </div>
        {{-- RIGHT: Pagination --}}
        <nav aria-label="Pagination" class="ms-auto">
            <ul class="pagination mb-0">

                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled">
                        <span class="page-link">
                            <i class="bx bx-chevron-left"></i>
                        </span>
                    </li>
                @else
                    <li class="page-item">
                        <button type="button" class="page-link"
                            wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled">
                            <i class="bx bx-chevron-left"></i>
                        </button>
                    </li>
                @endif
                {{-- Page numbers --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="page-item disabled">
                            <span class="page-link">{{ $element }}</span>
                        </li>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page === $paginator->currentPage())
                                <li class="page-item active">
                                    <span class="page-link">{{ $page }}</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <button type="button" class="page-link"
                                        wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                        wire:loading.attr="disabled">
                                        {{ $page }}
                                    </button>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach
                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <button type="button" class="page-link"
                            wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled">
                            <i class="bx bx-chevron-right"></i>
                        </button>
                    </li>
                @else
                    <li class="page-item disabled">
                        <span class="page-link">
                            <i class="bx bx-chevron-right"></i>
                        </span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif
