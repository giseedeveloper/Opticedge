@props([
    'action',
    'search' => '',
    'hidden' => [],
    'placeholder' => 'Search by name, email, or phone…',
    'class' => 'mb-4',
])

<form method="GET" action="{{ $action }}"
    class="admin-clay-panel js-user-live-search {{ $class }} flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
    @foreach ($hidden as $name => $value)
        @if ($value !== null && $value !== '')
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach
    @foreach (request()->only(['sort', 'direction']) as $name => $value)
        @if ($value !== null && $value !== '' && ! array_key_exists($name, $hidden))
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach
    <label for="user-live-search-input" class="sr-only">Search users</label>
    <input id="user-live-search-input" type="search" name="search" value="{{ $search }}"
        placeholder="{{ $placeholder }}"
        class="admin-prod-input w-full flex-1" autocomplete="off">
    @if ($search !== '')
        @php
            $clearParams = collect($hidden)->filter(fn ($value) => $value !== null && $value !== '')->all();
        @endphp
        <a href="{{ $action.(count($clearParams) ? '?'.http_build_query($clearParams) : '') }}"
            class="admin-prod-btn-ghost shrink-0">Clear</a>
    @endif
</form>

@once('admin-user-live-search-script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-user-live-search').forEach(function (form) {
                const input = form.querySelector('input[name="search"]');

                if (!input) {
                    return;
                }

                let timer;

                input.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        form.requestSubmit();
                    }, 300);
                });
            });
        });
    </script>
@endonce
