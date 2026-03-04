@if (session('success') || session('error') || session('warning') || session('info'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if (session('success'))
                showToast('success', @json(session('success')));
            @endif

            @if (session('error'))
                showToast('error', @json(session('error')));
            @endif

            @if (session('warning'))
                showToast('warning', @json(session('warning')));
            @endif

            @if (session('info'))
                showToast('info', @json(session('info')));
            @endif
        });
    </script>
@endif
