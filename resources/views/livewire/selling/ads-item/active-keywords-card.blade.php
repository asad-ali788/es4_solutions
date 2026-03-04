<div>
    @php
        $tz = config('timezone.market', 'America/Los_Angeles');
    @endphp
        <div class="row">
            <div class="col-lg-12">
                <div class="card mb-2">
                    <div class="card-body">
                        <div class="row">

                            {{-- TITLE --}}
                            <div class="col-lg-4">
                                <p class="mb-2">Active Keywords</p>
                            </div>

                            {{-- COUNTS --}}
                            <div class="col-lg-4 align-self-center">
                                <div class="text-lg-center">
                                    <div class="row">
                                        <div class="col-6 text-center">
                                            <p class="text-muted mb-1">SP</p>
                                            <h5 class="mb-0">{{ $spEnabled }}</h5>
                                            <small class="text-muted">
                                                {{ $spLastUpdated ? \Carbon\Carbon::parse($spLastUpdated)->setTimezone($tz)->format('h:i A T') : '—' }}
                                            </small>
                                        </div>

                                        <div class="col-6 text-center">
                                            <p class="text-muted mb-1">SB</p>
                                            <h5 class="mb-0">{{ $sbEnabled }}</h5>
                                            <small class="text-muted">
                                                {{ $sbLastUpdated ? \Carbon\Carbon::parse($sbLastUpdated)->setTimezone($tz)->format('h:i A T') : '—' }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ACTION --}}
                            <div class="col-lg-4 d-none d-lg-block">
                                <div class="float-end">
                                    <button type="button" class="btn btn-light btn-rounded" wire:click="refreshCountsAction"
                                        wire:loading.attr="disabled" wire:target="refreshCountsAction">
                                        <i class="mdi mdi-refresh" wire:loading.class="mdi-spin" wire:target="refreshCountsAction"></i>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>
