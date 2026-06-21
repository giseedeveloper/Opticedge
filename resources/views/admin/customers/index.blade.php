<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-0 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Users</p>
                <h1 class="admin-prod-title">All users</h1>
            </div>
            @php
                $searchQuery = request('search');
                $sortQuery = $sort ?? request('sort');
                $directionQuery = $direction ?? request('direction');
                $tabQuery = fn (?string $role) => array_filter([
                    'role' => $role,
                    'search' => $searchQuery,
                    'sort' => $sortQuery,
                    'direction' => $directionQuery,
                ]);
                $roleFilters = [
                    [
                        'role' => null,
                        'label' => 'All',
                        'href' => route('admin.customers.index', $tabQuery(null)),
                        'add' => null,
                    ],
                    [
                        'role' => 'subadmin',
                        'label' => 'Admins',
                        'href' => route('admin.customers.index', $tabQuery('subadmin')),
                        'add' => ['label' => 'Add admin', 'route' => route('admin.subadmins.create')],
                    ],
                    [
                        'role' => 'agent',
                        'label' => 'Agents',
                        'href' => route('admin.customers.index', $tabQuery('agent')),
                        'add' => ['label' => 'Add agent', 'route' => route('admin.agents.create')],
                    ],
                    [
                        'role' => 'teamleader',
                        'label' => 'Team leaders',
                        'href' => route('admin.customers.index', $tabQuery('teamleader')),
                        'add' => ['label' => 'Add team leader', 'route' => route('admin.customers.team-leaders.create')],
                    ],
                    [
                        'role' => 'regional_manager',
                        'label' => 'Regional managers',
                        'href' => route('admin.customers.index', $tabQuery('regional_manager')),
                        'add' => ['label' => 'Add regional manager', 'route' => route('admin.customers.regional-managers.create')],
                        'assign' => ['label' => 'Assign devices', 'route' => route('admin.customers.regional-managers.assign-devices')],
                    ],
                ];
            @endphp
            <div class="admin-prod-filter-row shrink-0" role="tablist" aria-label="Filter by role">
                @foreach ($roleFilters as $filter)
                    @php
                        $isActive = request('role') === $filter['role'] || ($filter['role'] === null && ! request('role'));
                    @endphp
                    @if ($isActive && $filter['add'])
                        <div
                            x-data="{ open: true }"
                            class="admin-prod-filter-dropdown"
                            @keydown.escape.window="open = false">
                            <button
                                type="button"
                                class="admin-prod-filter-tab admin-prod-filter-tab--active admin-prod-filter-tab--menu"
                                :aria-expanded="open"
                                aria-haspopup="menu"
                                @click="open = !open">
                                <span>{{ $filter['label'] }}</span>
                                <svg class="h-4 w-4 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-1"
                                @click.outside="open = false"
                                class="admin-prod-filter-menu"
                                role="menu">
                                <a href="{{ $filter['add']['route'] }}" class="admin-prod-filter-menu-item" role="menuitem"
                                    @click="open = false">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    {{ $filter['add']['label'] }}
                                </a>
                                @if (! empty($filter['assign']))
                                    <a href="{{ $filter['assign']['route'] }}" class="admin-prod-filter-menu-item" role="menuitem"
                                        @click="open = false">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        {{ $filter['assign']['label'] }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <a href="{{ $filter['href'] }}"
                            class="admin-prod-filter-tab {{ $isActive ? 'admin-prod-filter-tab--active' : '' }}"
                            @if ($isActive) aria-current="page" @endif>
                            {{ $filter['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ $errors->first() }}</div>
        @endif

        @include('admin.partials.user-live-search', [
            'action' => route('admin.customers.index'),
            'search' => $search ?? '',
            'ajax' => true,
            'hidden' => [
                'role' => request('role'),
                'sort' => $sort ?? request('sort'),
                'direction' => $direction ?? request('direction'),
            ],
        ])

        <div class="admin-clay-panel overflow-hidden" id="users-directory-panel">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[860px]" data-no-datatable>
                    <thead id="users-directory-thead">
                        @include('admin.customers.partials.directory-thead-row', [
                            'sort' => $sort,
                            'direction' => $direction,
                            'hasTeamLeaderColumn' => $hasTeamLeaderColumn,
                        ])
                    </thead>
                    <tbody id="users-directory-tbody">
                        <tr>
                            <td colspan="{{ $hasTeamLeaderColumn ? 9 : 8 }}" class="text-center text-slate-500 py-10">
                                Loading users…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="users-directory-pagination"></div>
        </div>
    </div>

    @push('styles')
        <style>
            #users-directory-panel.is-loading {
                opacity: 0.65;
                pointer-events: none;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            jQuery(function ($) {
                var $panel = $('#users-directory-panel');
                var $form = $('.js-user-live-search');
                var endpoint = $form.attr('action');
                var xhr = null;
                var debounceTimer = null;

                function collectParams(page) {
                    var params = {};

                    $form.find('input[name]').each(function () {
                        var $input = $(this);
                        var value = $.trim($input.val());

                        if (value !== '') {
                            params[$input.attr('name')] = value;
                        }
                    });

                    if (page) {
                        params.page = page;
                    }

                    return params;
                }

                function updateUrl(params) {
                    var query = $.param(params);

                    window.history.pushState(params, '', endpoint + (query ? '?' + query : ''));
                }

                function applyResults(data) {
                    $('#users-directory-tbody').html(data.tbody);
                    $('#users-directory-pagination').html(data.pagination);
                    $('#users-directory-thead').html(data.thead);
                }

                function loadUsers(page, options) {
                    options = options || {};
                    var params = collectParams(page);

                    if (xhr) {
                        xhr.abort();
                    }

                    $panel.addClass('is-loading');

                    xhr = $.ajax({
                        url: endpoint,
                        method: 'GET',
                        data: params,
                        dataType: 'json',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    })
                        .done(function (data) {
                            applyResults(data);

                            if (options.pushState !== false) {
                                updateUrl(params);
                            }
                        })
                        .fail(function (jqXHR, textStatus) {
                            if (textStatus === 'abort') {
                                return;
                            }

                            $('#users-directory-tbody').html(
                                '<tr><td colspan="{{ $hasTeamLeaderColumn ? 9 : 8 }}" class="text-center text-red-600 py-10">Could not load users. Please refresh the page.</td></tr>'
                            );
                            $('#users-directory-pagination').empty();
                        })
                        .always(function () {
                            $panel.removeClass('is-loading');
                            xhr = null;
                        });
                }

                loadUsers(new URLSearchParams(window.location.search).get('page'), { pushState: false });

                $form.on('submit', function (event) {
                    event.preventDefault();
                    loadUsers();
                });

                $form.find('input[name="search"]').on('input', function () {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function () {
                        loadUsers();
                    }, 300);
                });

                $panel.on('click', '.admin-prod-sort-link', function (event) {
                    event.preventDefault();

                    var url = new URL($(this).attr('href'), window.location.origin);
                    var sort = url.searchParams.get('sort');
                    var direction = url.searchParams.get('direction');

                    $form.find('input[name="sort"]').remove();
                    $form.find('input[name="direction"]').remove();

                    if (sort) {
                        $('<input>', { type: 'hidden', name: 'sort', value: sort }).appendTo($form);
                    }

                    if (direction) {
                        $('<input>', { type: 'hidden', name: 'direction', value: direction }).appendTo($form);
                    }

                    loadUsers();
                });

                $panel.on('click', '#users-directory-pagination a', function (event) {
                    event.preventDefault();

                    var url = new URL($(this).attr('href'), window.location.origin);
                    var page = url.searchParams.get('page');

                    loadUsers(page || 1);
                });

                window.addEventListener('popstate', function () {
                    var urlParams = new URLSearchParams(window.location.search);

                    $form.find('input[name="search"]').val(urlParams.get('search') || '');
                    $form.find('input[name="role"]').remove();
                    $form.find('input[name="sort"]').remove();
                    $form.find('input[name="direction"]').remove();

                    ['role', 'sort', 'direction'].forEach(function (name) {
                        var value = urlParams.get(name);

                        if (value) {
                            $('<input>', { type: 'hidden', name: name, value: value }).appendTo($form);
                        }
                    });

                    loadUsers(urlParams.get('page'), { pushState: false });
                });
            });
        </script>
    @endpush
</x-admin-layout>
