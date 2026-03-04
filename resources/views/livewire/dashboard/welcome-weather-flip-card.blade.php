<div class="col-12 col-xl-8 d-flex" wire:key="welcome-weather-flip-card">
    @php
        $storageKey = $storageKey ?? 'dashboard:welcome-weather-flip-card';
    @endphp
    <div class="card w-100 flip-card p-0 position-relative" wire:ignore.self x-data="flipWeatherCard({
        intervalMs: 5000,
        storageKey: @js($storageKey),
        defaultLocationKey: @js($locationKey ?? 'us_ca'),
    })"
        x-init="init()" @mouseenter="pause()" @mouseleave="resume()">
        {{-- Real card content hidden while loading --}}
        <div class="card-body p-0">
            <div class="flip-stage">
                <div class="flip-inner" :class="{ 'is-flipped': flipped }">

                    {{-- ================= FRONT (WELCOME) ================= --}}
                    <div class="flip-face flip-front">
                        <div class="flip-face-content">
                            <div class="d-flex justify-content-end gap-2 mb-2">
                                <button type="button" class="btn btn-sm"
                                    :class="locked ? 'btn-warning' : 'btn-outline-secondary'" @click="toggleLock()"
                                    :title="locked ? 'Locked: auto-flip disabled' : 'Lock: stop auto-flip'">
                                    <i class="mdi" :class="locked ? 'mdi-lock' : 'mdi-lock-open-variant'"></i>
                                </button>

                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="toggle()"
                                    :disabled="locked" :title="locked ? 'Unlock to flip' : 'Flip'">
                                    <i class="mdi mdi-swap-vertical"></i>
                                </button>
                            </div>

                            <div class="row g-3 align-items-center">
                                <div class="col-8 col-lg-9 mt-0">
                                    <h5 class="text-primary mb-1">Welcome Back ! 🎉</h5>
                                    <p class="mb-2 text-muted">Here’s your daily overview</p>

                                    <div class="text-muted">
                                        <p class="mb-1">
                                            <i class="mdi mdi-circle-medium align-middle text-primary me-1"></i>
                                            Track your campaign performance
                                        </p>
                                        <p class="mb-0">
                                            <i class="mdi mdi-circle-medium align-middle text-primary me-1"></i>
                                            Boost results with AI suggestions
                                        </p>
                                    </div>
                                </div>

                                <div class="col-4 col-lg-3 text-center">
                                    <img src="{{ asset('assets/images/dashboard-computer.png') }}" alt=""
                                        class="img-fluid jump">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ================= BACK (WEATHER) ================= --}}
                    <div class="flip-face flip-back">
                        <div class="flip-face-content">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <h6 class="mb-0">Next 5 days weather</h6>

                                    {{-- Dropdown --}}
                                    <div style="min-width: 155px; max-width: 210px;" wire:ignore>
                                        <select class="form-select form-select-sm" x-model="locationKey"
                                            @change="changeLocation()" title="Weather location">
                                            @foreach ($locations as $key => $loc)
                                                <option value="{{ $key }}">{{ $loc['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>


                                    <span class="text-muted small">
                                        @if (($forecast['ok'] ?? false) && !empty($forecast['meta']))
                                            {{ $forecast['meta']['city'] ?? $region }}
                                            {{ !empty($forecast['meta']['country']) ? ', ' . $forecast['meta']['country'] : '' }}
                                            ·
                                            @php
                                                $meta = is_array($forecast['meta'] ?? null) ? $forecast['meta'] : [];
                                                $units = (string) ($meta['units'] ?? 'metric');
                                            @endphp

                                            {{ $units === 'metric' ? '°C' : '°F' }}
                                        @else
                                            {{ $region }}{{ $country ? ', ' . $country : '' }}
                                        @endif
                                    </span>
                                </div>

                                <div class="d-flex gap-2 flex-shrink-0">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        wire:click.stop="refreshForecast" title="Refresh">
                                        <i class="mdi mdi-refresh"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm"
                                        :class="locked ? 'btn-warning' : 'btn-outline-secondary'" @click="toggleLock()"
                                        :title="locked ? 'Locked: auto-flip disabled' : 'Lock: stop auto-flip'">
                                        <i class="mdi" :class="locked ? 'mdi-lock' : 'mdi-lock-open-variant'"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="toggle()"
                                        :disabled="locked" :title="locked ? 'Unlock to flip' : 'Flip'">
                                        <i class="mdi mdi-swap-vertical"></i>
                                    </button>
                                </div>
                            </div>

                            <hr class="my-1">

                            @if (!($forecast['ok'] ?? false))
                                <div class="alert alert-warning mb-0">
                                    {{ $forecast['error'] ?? 'Unable to load weather.' }}
                                </div>
                            @else
                                <div class="weather-strip">
                                    @foreach ($forecast['days'] ?? [] as $i => $day)
                                        @php
                                            $icon = $day['icon'] ?? '';
                                            $iconUrl = $icon
                                                ? "https://openweathermap.org/img/wn/{$icon}@2x.png"
                                                : null;
                                            $isToday = $i === 0;
                                        @endphp

                                        <div class="weather-day {{ $isToday ? 'is-today' : '' }}">
                                            <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                                                <div class="fw-semibold small">
                                                    {{ $day['label'] ?? ($day['date'] ?? '') }}
                                                </div>

                                                @if ($isToday)
                                                    <span class="weather-badge">
                                                        <i class="mdi mdi-star-four-points-outline"></i> Today
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="weather-icon">
                                                @if ($iconUrl)
                                                    <img src="{{ $iconUrl }}" alt="">
                                                @else
                                                    <i class="mdi mdi-weather-partly-cloudy fs-4"></i>
                                                @endif
                                            </div>

                                            <div class="fw-semibold">
                                                {{ $day['max'] ?? '-' }}°
                                                <span class="text-muted">/ {{ $day['min'] ?? '-' }}°</span>
                                            </div>

                                            <div class="weather-desc text-muted small">
                                                {{ ucfirst($day['desc'] ?? '—') }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>

        @once
            @push('scripts')
                <script>
                    document.addEventListener('alpine:init', () => {
                        Alpine.data('flipWeatherCard', ({
                            intervalMs = 5000,
                            storageKey = 'dashboard:flip-card',
                            defaultLocationKey = 'us_ca'
                        } = {}) => ({
                            flipped: true,
                            locked: true,
                            paused: false,
                            timer: null,

                            locationKey: defaultLocationKey,

                            init() {
                                this.restoreFlipState();
                                this.restoreLocationAndSync();
                                this.start();
                            },

                            restoreFlipState() {
                                try {
                                    const raw = localStorage.getItem(storageKey + ':flip');
                                    if (!raw) return;
                                    const state = JSON.parse(raw);
                                    this.flipped = !!state.flipped;
                                    this.locked = !!state.locked;
                                } catch (e) {}
                            },

                            persistFlipState() {
                                try {
                                    localStorage.setItem(storageKey + ':flip', JSON.stringify({
                                        flipped: this.flipped,
                                        locked: this.locked,
                                    }));
                                } catch (e) {}
                            },

                            start() {
                                this.stop();
                                if (this.locked) return;

                                this.timer = setInterval(() => {
                                    if (this.paused || this.locked) return;
                                    this.flipped = !this.flipped;
                                    this.persistFlipState();
                                }, intervalMs);
                            },

                            stop() {
                                if (this.timer) {
                                    clearInterval(this.timer);
                                    this.timer = null;
                                }
                            },

                            toggle() {
                                if (this.locked) return;
                                this.flipped = !this.flipped;
                                this.persistFlipState();
                            },

                            toggleLock() {
                                this.locked = !this.locked;
                                this.persistFlipState();

                                if (this.locked) this.stop();
                                else this.start();
                            },

                            pause() {
                                this.paused = true;
                            },
                            resume() {
                                this.paused = false;
                            },

                            restoreLocationAndSync() {
                                try {
                                    const raw = localStorage.getItem(storageKey + ':location');
                                    if (raw) {
                                        const saved = JSON.parse(raw);
                                        if (saved && saved.locationKey) {
                                            this.locationKey = saved.locationKey;
                                        }
                                    }
                                } catch (e) {}

                                if (this.$wire && typeof this.$wire.setLocation === 'function') {
                                    this.$wire.setLocation(this.locationKey);
                                }
                            },

                            changeLocation() {
                                try {
                                    localStorage.setItem(storageKey + ':location', JSON.stringify({
                                        locationKey: this.locationKey
                                    }));
                                } catch (e) {}

                                if (this.$wire && typeof this.$wire.setLocation === 'function') {
                                    this.$wire.setLocation(this.locationKey);
                                }
                            },
                        }));
                    });
                </script>
            @endpush
        @endonce
    </div>
</div>
