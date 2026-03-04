<div class="position-relative header-cmd-search" wire:keydown.escape="close">

    <div class="position-relative">
        <input type="text" class="form-control ps-5 large-search-bar" placeholder="Quick search...      Ctrl + Space"
            wire:model.live.debounce.350ms="query" wire:focus="openDropdown" wire:keydown="openDropdown"
            wire:keydown.ctrl.space.window="openDropdown" wire:keydown.enter.prevent="enter" autocomplete="off"
            x-ref="quickSearchInput">

        <span class="bx bx-search-alt position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"
            style="font-size: 1.1rem; pointer-events:none;"></span>
    </div>

    @if ($open)
        <div class="dropdown-menu show mt-2 p-0 shadow-md focus-search"
            style="width:min(400px, 92vw); max-height:360px; overflow:auto; z-index:1062; border-radius:10px;"
            wire:click.stop>

            {{-- If query is empty/short --}}
            @if (strlen(trim($query)) < 2)

                @if (!empty($recentItems ?? []))
                    <div class="px-3 pt-2 small fw-semibold text-muted">Recent</div>

                    <div class="list-group list-group-flush">
                        @foreach ($recentItems ?? [] as $item)
                            <button type="button" wire:key="recent-{{ $item['id'] }}"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 w-100 waves-effect"
                                wire:click="go('{{ $item['url'] }}', '{{ $item['id'] }}', '{{ addslashes($item['label']) }}', '{{ $item['module'] }}')">

                                <i class="bx bx-history text-muted" style="font-size:18px;"></i>
                                {{-- Middle text --}}
                                <div class="flex-grow-1 text-start overflow-hidden">
                                    <div class="fw-semibold text-truncate">{{ $item['label'] }}</div>
                                    <div class="small text-muted text-truncate">{{ $item['module'] }}</div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="p-3 text-center text-muted">
                        Start typing to search
                    </div>
                @endif
            @else
                {{-- Results grouped by module --}}
                @forelse($results as $module => $items)
                    <div class="px-3 pt-2 small fw-semibold text-muted"
                        wire:key="module-{{ \Illuminate\Support\Str::slug($module) }}">
                        {{ $module }}
                    </div>

                    <div class="list-group list-group-flush">
                        @foreach ($items as $item)
                            <button type="button" wire:key="result-{{ $item['id'] }}"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 waves-effect"
                                wire:click="go('{{ $item['url'] }}', '{{ $item['id'] }}', '{{ addslashes($item['label']) }}', '{{ $item['module'] }}')">

                                <i class="bx bx-search text-muted" style="font-size:1.15rem;"></i>

                                <div class="flex-grow-1 text-start">
                                    <div class="fw-semibold lh-sm">{{ $item['label'] }}</div>
                                    <div class="small text-muted">{{ $item['module'] }}</div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @empty
                    <div class="p-3 text-center text-muted">
                        No results found
                    </div>
                @endforelse

                {{-- Query typed => recent matches then results --}}
                @if (!empty($recentItems ?? []))
                    <div class="px-3 pt-2 small fw-semibold text-muted">Recent</div>

                    <div class="list-group list-group-flush">
                        @foreach ($recentItems ?? [] as $item)
                            <button type="button" wire:key="result-{{ $item['id'] }}"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-2 w-100 waves-effect"
                                wire:click="go('{{ $item['url'] }}', '{{ $item['id'] }}', '{{ addslashes($item['label']) }}', '{{ $item['module'] }}')">

                                <i class="bx bx-history text-muted" style="font-size:18px;"></i>

                                <div class="flex-grow-1 text-start overflow-hidden">
                                    <div class="fw-semibold text-truncate">{{ $item['label'] }}</div>
                                    <div class="small text-muted text-truncate">{{ $item['module'] }}</div>
                                </div>
                            </button>
                        @endforeach
                    </div>

                    <div class="border-top"></div>
                @endif
            @endif
            <div class="p-1 small text-muted border-top text-end">
                <i class="bx bx-search-alt"></i>
                Quick search
            </div>
        </div>
        {{-- Click-outside backdrop --}}
        <div class="position-fixed top-0 start-0 w-100 h-100" style="z-index:1040;" wire:click="close"></div>
    @endif

    <style>
        .header-cmd-search {
            z-index: 1060;
        }
        .large-search-bar{
            width: 114% !important
        }
    </style>
    @once
        <script>
            document.addEventListener('keydown', function(e) {
                // Ctrl + Space
                if (e.ctrlKey && (e.code === 'Space' || e.keyCode === 32)) {
                    e.preventDefault();

                    const input = document.querySelector('.header-cmd-search input.form-control');
                    if (input) {
                        input.focus({
                            preventScroll: true
                        });

                        // call Livewire method without changing PHP
                        const root = input.closest('[wire\\:id]');
                        if (root && window.Livewire) {
                            window.Livewire.find(root.getAttribute('wire:id'))?.call('openDropdown');
                        }
                    }
                }
            }, true);
        </script>
    @endonce


</div>
