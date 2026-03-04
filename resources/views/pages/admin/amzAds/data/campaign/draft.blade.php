@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-0">Campaign Drafts</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.ads.campaigns.create') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Campaign Create
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <livewire:campaign.campaign-drafts-list />
        </div>
    </div>
@endsection
