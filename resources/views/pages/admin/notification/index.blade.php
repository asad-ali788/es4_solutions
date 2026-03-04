@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Notifications</h4>
                <p class="text-muted">A place for all your related work notifications</p>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <!-- Left sidebar -->
            <div class="email-leftbar card">
                <a href="{{ route('admin.notification.refresh') }}"
                    class="btn btn-success btn-block waves-effect waves-light">
                    Refresh
                </a>
                <div class="mail-list mt-4">
                    <a href="{{ route('admin.notification.index') }}"
                        class="d-flex align-items-center {{ request('status') == null ? 'active' : '' }}">
                        <i class="mdi mdi-inbox-full-outline me-2" style="font-size: 1.2rem;"></i>
                        <span>All</span>
                    </a>

                    <a href="{{ route('admin.notification.index', ['status' => 'unread']) }}"
                        class="d-flex align-items-center justify-content-between {{ request('status') == 'unread' ? 'active' : '' }}">
                        <div class="d-flex align-items-center">
                            <i class="mdi mdi-email-outline me-2" style="font-size: 1.2rem;"></i>
                            <span>Unread</span>
                        </div>
                        <span class="badge bg-danger ms-2">{{ $unreadCount }}</span>
                    </a>

                    <a href="{{ route('admin.notification.index', ['status' => 'read']) }}"
                        class="d-flex align-items-center {{ request('status') == 'read' ? 'active' : '' }}">
                        <i class="mdi mdi-email-check-outline me-2" style="font-size: 1.2rem;"></i>
                        <span>Read</span>
                    </a>
                    @can('notification.trash')
                        <a href="{{ route('admin.notification.index', ['status' => 'trashed']) }}"
                            class="d-flex align-items-center {{ request('status') == 'trashed' ? 'active' : '' }}">
                            <i class="mdi mdi-trash-can-outline me-2" style="font-size: 1.2rem;"></i>
                            <span>Trash</span>
                        </a>
                    @endcan
                </div>
            </div>
            <!-- End Left sidebar -->

            <!-- Right Sidebar -->
            <div class="email-rightbar mb-3">
                <div class="card">
                    @if ($notification_details)
                        <div class="card-body">
                            {{-- Back Button as Header --}}
                            <div class="d-flex align-items-center mb-4">
                                <a href="{{ route('admin.notification.index', request()->query()) }}"
                                    class="text-primary d-flex align-items-center text-decoration-none">
                                    <i class="mdi mdi-arrow-left-thin-circle-outline font-size-20 me-1"></i>
                                    <strong>Back to Notifications</strong>
                                </a>
                            </div>

                            {{-- Notification Content --}}
                            <div class="d-flex mb-4">

                                <div class="flex-grow-1">
                                    <h5 class="font-size-16 mt-1">{{ $notification_details->title }}</h5>
                                    <small class="text-muted">Created:
                                        {{ \Carbon\Carbon::parse($notification_details->created_date)->format('d M Y, h:i A') }}</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-4 justify-content-end">
                                {{-- Avatar --}}
                                <div class="flex-shrink-0 me-2">
                                    @if ($notification_details->handlerUser)
                                        <img src="{{ $notification_details->handlerUser->profile ? asset('storage/' . $notification_details->handlerUser->profile) : $notification_details->handlerUser->profile_photo_url }}"
                                            class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;"
                                            data-bs-toggle="tooltip"
                                            title="{{ $notification_details->handlerUser->name ?? 'Not Assigned' }}">
                                    @else
                                        <span
                                            class="rounded-circle header-profile-user d-flex align-items-center justify-content-center text-white bg-secondary"
                                            style="width: 32px; height: 32px; font-size: 16px;" data-bs-toggle="tooltip"
                                            title="Not Assigned">
                                            ?
                                        </span>
                                    @endif
                                </div>

                                {{-- Info: Title + Name --}}
                                <div class="flex-grow-1">
                                    <div class="text-muted small">Handler</div>
                                    <div class="fw-semibold">
                                        {{ $notification_details->handlerUser->name ?? 'Not Assigned' }}
                                    </div>
                                </div>
                            </div>

                            {{-- Stock Details Table --}}
                            @if (!empty($details_table))
                                <div class="justify-content-between row">
                                    <div class="col">
                                        <h5 class="font-size-16">Stock Details</h5>
                                    </div>
                                    <div class="col-lg-3">
                                        <form method="GET"
                                            action="{{ route('admin.notification.index', ['id' => $notification_details->id]) }}"
                                            class="d-inline-block">
                                            {{-- Keep other query parameters like status & page --}}
                                            <input type="hidden" name="status" value="{{ request('status') }}">
                                            <input type="hidden" name="page" value="{{ request('page') }}">
                                            <x-elements.search-box name="detail_search" />
                                        </form>
                                    </div>
                                </div>
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table table-sm">
                                            <tr>
                                                <th>#</th>
                                                <th>SKU</th>
                                                <th>Quantity Available</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($details_table as $detail)
                                                <tr>
                                                    <th scope="row">{{ $loop->iteration }}</th>
                                                    {{-- <td>{{ ($details_table->currentPage() - 1) * $details_table->perPage() + $loop->iteration }} --}}
                                                    </td>
                                                    <td>{{ $detail->sku }}</td>
                                                    @if ($detail->stock_status)
                                                        <td class="text-success">{{ $detail->quantity_available }}</td>
                                                    @else
                                                        <td>{{ $detail->quantity_available }}</td>
                                                    @endif
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No details found.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if ($details_table->hasPages())
                                    <div class="mt-3" id="notification_details">
                                        {{ $details_table->appends(request()->except('detail_page'))->links('pagination::bootstrap-5') }}
                                    </div>
                                @endif
                            @else
                                <p class="text-muted">No detail data available.</p>
                            @endif
                            <hr />
                            {{-- Reassignment Form --}}
                            @if (request('status') != 'trashed')
                                <h5 class="font-size-16 mt-4 mb-2">Reassign Notification</h5>
                                <form method="POST"
                                    action="{{ route('admin.notification.assign-user', $notification_details->id) }}">
                                    @csrf
                                    <div class="mb-3 row align-items-center">
                                        <label class="col-sm-2 col-form-label">Assign to</label>
                                        <div class="col-sm-6">
                                            <select name="assigned_user_id" class="form-select" required>
                                                <option value="0"
                                                    {{ is_null($notification_details->assigned_user_id) ? 'selected' : '' }}>
                                                    — Not Assigned —
                                                </option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}"
                                                        {{ $notification_details->assigned_user_id == $user->id ? 'selected' : '' }}>
                                                        {{ $user->name }} ({{ $user->email }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bx bx-user-check me-1"></i> Assign
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @else
                        <div class="card-body">
                            <div class="justify-content-between row">
                                <div class="col">
                                </div>
                                <div class="col-lg-3">
                                    <form method="GET" action="{{ route('admin.notification.index') }}"
                                        class="d-inline-block me-2 mb-2">
                                        <input type="hidden" name="status" value="{{ request('status') }}">
                                        <!-- Search -->
                                        <x-elements.search-box />
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Subject</th>
                                            <th>Notification Date</th>
                                            <th>Read Date</th>
                                            <th>Assigned User</th>
                                            <th>Read/Unread</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($notification as $index => $note)
                                            <tr>
                                                <th scope="row">{{ $index + 1 }}</th>
                                                <td>
                                                    <a href="{{ route('admin.notification.index', ['id' => $note->id] + request()->query()) }}"
                                                        class="text-primary">{{ $note->title ?? 'N/A' }}</a>
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($note->created_date)->format('d M Y, h:i A') ?? 'N/A' }}
                                                </td>
                                                <td>
                                                    @if ($note->read_date)
                                                        {{ \Carbon\Carbon::parse($note->read_date)->format('d M Y, h:i A') ?? 'N/A' }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($note->user)
                                                        <img src="{{ $note->user->profile ? asset('storage/' . $note->user->profile) : $note->user->profile_photo_url }}"
                                                            alt="" class="rounded-circle"
                                                            style="width: 35px; height: 35px;" data-bs-toggle="tooltip"
                                                            title="{{ $note->user->name }}">

                                                        {{-- <img src="{{ asset('storage/' . $note->user->profile) }}"
                                                            alt="Profile Image" class="rounded-circle header-profile-user"
                                                            style="width: 35px; height: 35px; object-fit: cover;"
                                                            data-bs-toggle="tooltip" title="{{ $note->user->name }}"> --}}
                                                    @else
                                                        <span
                                                            class="rounded-circle header-profile-user d-flex align-items-center justify-content-center text-white bg-secondary"
                                                            style="width: 32px; height: 32px; font-size: 16px;"
                                                            data-bs-toggle="tooltip" title="Not Assigned">
                                                            ?
                                                        </span>
                                                    @endif
                                                </td>
                                                @if (is_null($note->deleted_at))
                                                    <td>
                                                        @if ($note->read_status == 0)
                                                            <form method="POST"
                                                                action="{{ route('admin.notification.toggle-status', $note->id) }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit"
                                                                    class="btn-label btn btn-success btn-sm">
                                                                    <i class="bx bx-check label-icon"></i> Read
                                                                </button>
                                                            </form>
                                                        @else
                                                            <form method="POST"
                                                                action="{{ route('admin.notification.toggle-status', $note->id) }}"
                                                                onsubmit="return confirm('Are you sure you want to mark this as Unread?');">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit"
                                                                    class="btn-label btn btn-primary btn-sm">
                                                                    <i class="bx bx-check-double label-icon"></i> Unread
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                @else
                                                    <td><span class="me-1 badge bg-danger">Disabled</span></td>
                                                @endif
                                                <td>
                                                    <div class="dropdown" style="position: relative;">
                                                        <a href="#" class="dropdown-toggle card-drop"
                                                            data-bs-toggle="dropdown">
                                                            <i class="mdi mdi-dots-horizontal font-size-18"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a href="{{ route('admin.notification.index', ['id' => $note->id] + request()->query()) }}"
                                                                    class="dropdown-item text-primary">
                                                                    <i
                                                                        class="mdi mdi-eye-check-outline font-size-18 text-primary me-1"></i>
                                                                    View Details
                                                                </a>
                                                            </li>
                                                            @can('notification.delete')
                                                                @if (is_null($note->deleted_at))
                                                                    <li>
                                                                        <a href="{{ route('admin.notification.destroy', ['id' => $note->id] + request()->query()) }}"
                                                                            class="dropdown-item text-danger">
                                                                            <i
                                                                                class="mdi mdi-trash-can font-size-16 text-danger me-1"></i>
                                                                            Delete
                                                                        </a>
                                                                    </li>
                                                                @endif
                                                            @endcan
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">No notifications found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div><!-- card -->
                <div class="row">
                    <div class="mt-3" id="notification">
                        @if (!$notification_details)
                            {{ $notification->appends(request()->query())->links('pagination::bootstrap-5') ?? 'N/A' }}
                        @endif
                    </div>
                </div>
            </div> <!-- end Col-9 -->
        </div>
    </div>
    <!-- end row -->
@endsection
