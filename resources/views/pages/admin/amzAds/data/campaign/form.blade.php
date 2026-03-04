@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-0">Create New Campaign</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.ads.campaigns') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Campaign SP
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="px-3 pt-3 pb-2 border-bottom d-flex align-items-center justify-content-between">
            <h5 class="mb-0 fw-semibold">Generate Campaign</h5>

            <a href="{{ route('admin.campaigns.drafts.page') }}"
                class="btn btn-sm btn-success btn-rounded waves-effect waves-light">
                <i class="mdi mdi-note-multiple me-1"></i>
                View drafts
            </a>
        </div>

        <div class="card-body">
            <livewire:campaign.campaign-create-form :campaignType="$campaignType" />
        </div>
    </div>

    @if (session('toast_success'))
        <script>
            window.addEventListener('load', () => {
                @foreach (session('toast_success') as $msg)
                    showToast('success', @json($msg), 10000);
                @endforeach
            });
        </script>
    @endif

    @if (session('toast_errors'))
        <script>
            window.addEventListener('load', () => {
                @foreach (session('toast_errors') as $msg)
                    showToast('error', @json($msg), 10000);
                @endforeach
            });
        </script>
    @endif
@endsection
