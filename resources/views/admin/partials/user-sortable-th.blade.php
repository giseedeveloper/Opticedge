@props([
    'column',
    'label',
    'sort',
    'direction',
    'align' => 'start',
])

@php
    $isActive = $sort === $column;
    $nextDirection = ($isActive && $direction === 'asc') ? 'desc' : 'asc';
    $params = array_filter([
        'search' => request('search'),
        'role' => request('role'),
        'sort' => $column,
        'direction' => $nextDirection,
    ], fn ($value) => $value !== null && $value !== '');
    $href = request()->url().(count($params) ? '?'.http_build_query($params) : '');
@endphp

<th scope="col" class="admin-prod-th {{ $align === 'end' ? 'admin-prod-th--end' : '' }}">
    <a href="{{ $href }}" class="admin-prod-sort-link {{ $isActive ? 'admin-prod-sort-link--active' : '' }}"
        @if ($isActive) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
        <span>{{ $label }}</span>
        <span class="admin-prod-sort-link__icon" aria-hidden="true">
            @if ($isActive)
                @if ($direction === 'asc')
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 3a.75.75 0 01.53.22l3 3a.75.75 0 01-1.06 1.06L10.75 5.56V17a.75.75 0 01-1.5 0V5.56L7.53 7.28a.75.75 0 01-1.06-1.06l3-3A.75.75 0 0110 3z"
                            clip-rule="evenodd" />
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 17a.75.75 0 01-.53-.22l-3-3a.75.75 0 111.06-1.06L9.25 14.44V3a.75.75 0 011.5 0v11.44l1.97-1.97a.75.75 0 111.06 1.06l-3 3A.75.75 0 0110 17z"
                            clip-rule="evenodd" />
                    </svg>
                @endif
            @else
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        d="M10 3.75a.75.75 0 01.53.22l2.25 2.25a.75.75 0 11-1.06 1.06L10.75 5.56V9a.75.75 0 01-1.5 0V5.56L8.28 7.28a.75.75 0 11-1.06-1.06l2.25-2.25A.75.75 0 0110 3.75zM10 16.25a.75.75 0 01-.53-.22l-2.25-2.25a.75.75 0 111.06-1.06L9.25 14.44V11a.75.75 0 011.5 0v3.44l1.97-1.97a.75.75 0 111.06 1.06l-2.25 2.25a.75.75 0 01-.53.22z" />
                </svg>
            @endif
        </span>
    </a>
</th>
