<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Partners</p>
                <h1 class="admin-prod-title">{{ $user->name }}</h1>
                <p class="admin-prod-subtitle">Dealer account details and locations.</p>
            </div>
            @php
                $fromCustomers = request('from') === 'customers';
                $backUrl = $fromCustomers
                    ? route('admin.customers.index', request()->only('role'))
                    : route('admin.dealers.index');
                $backLabel = $fromCustomers ? 'Back to all users' : 'Back to dealers';
            @endphp
            <a href="{{ $backUrl }}" class="admin-prod-back shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ $backLabel }}
            </a>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-6" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-prod-alert admin-prod-alert--error mb-6" role="alert">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="admin-prod-alert admin-prod-alert--error mb-6" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="admin-clay-panel overflow-hidden mb-6">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Applicant information</h2>
                <p class="admin-prod-form-hint">Personal details and application status.</p>
            </div>
            <dl class="admin-prod-detail-body">
                <div class="admin-prod-detail-row">
                    <dt class="admin-prod-detail-dt">Business name</dt>
                    <dd class="admin-prod-detail-dd font-bold">{{ $user->business_name }}</dd>
                </div>
                <div class="admin-prod-detail-row">
                    <dt class="admin-prod-detail-dt">Full name</dt>
                    <dd class="admin-prod-detail-dd">{{ $user->name }}</dd>
                </div>
                <div class="admin-prod-detail-row">
                    <dt class="admin-prod-detail-dt">Email</dt>
                    <dd class="admin-prod-detail-dd">
                        <a href="mailto:{{ $user->email }}" class="admin-prod-link">{{ $user->email }}</a>
                    </dd>
                </div>
                <div class="admin-prod-detail-row">
                    <dt class="admin-prod-detail-dt">Phone</dt>
                    <dd class="admin-prod-detail-dd">{{ $user->phone ?? 'N/A' }}</dd>
                </div>
                <div class="admin-prod-detail-row">
                    <dt class="admin-prod-detail-dt">Status</dt>
                    <dd class="admin-prod-detail-dd">
                        @php
                            $st = $user->status;
                            $stClass =
                                $st === 'active'
                                    ? 'admin-prod-dealer-status--active'
                                    : ($st === 'pending'
                                        ? 'admin-prod-dealer-status--pending'
                                        : 'admin-prod-dealer-status--suspended');
                        @endphp
                        <span class="admin-prod-dealer-status {{ $stClass }}">{{ ucfirst($st) }}</span>
                    </dd>
                </div>
                <div class="admin-prod-detail-row">
                    <dt class="admin-prod-detail-dt">Joined</dt>
                    <dd class="admin-prod-detail-dd font-variant-numeric">{{ $user->created_at->format('F j, Y') }}</dd>
                </div>
            </dl>
        </div>

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden mb-6">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Update dealer information</h2>
                <p class="admin-prod-form-hint">Change contact details, business name, email, or set a new password. Leave password blank to keep the current one.</p>
            </div>
            <form method="POST" action="{{ route('admin.dealers.update', $user) }}" class="admin-prod-form-body space-y-6">
                @csrf
                @method('PATCH')
                <div>
                    <label for="edit_name" class="admin-prod-label">Full name</label>
                    <input type="text" id="edit_name" name="name" value="{{ old('name', $user->name) }}" required
                        class="admin-prod-input" autocomplete="name">
                    @error('name')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="edit_business_name" class="admin-prod-label">Business name</label>
                    <input type="text" id="edit_business_name" name="business_name"
                        value="{{ old('business_name', $user->business_name) }}" required class="admin-prod-input"
                        autocomplete="organization">
                    @error('business_name')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="edit_email" class="admin-prod-label">Email</label>
                    <input type="email" id="edit_email" name="email" value="{{ old('email', $user->email) }}" required
                        class="admin-prod-input" autocomplete="email">
                    @error('email')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="edit_phone" class="admin-prod-label">Phone</label>
                    <input type="tel" id="edit_phone" name="phone" value="{{ old('phone', $user->phone) }}"
                        class="admin-prod-input" autocomplete="tel" placeholder="e.g. +255 …">
                    @error('phone')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="edit_password" class="admin-prod-label">New password</label>
                    <input type="password" id="edit_password" name="password" class="admin-prod-input"
                        autocomplete="new-password" placeholder="Leave blank to keep current password">
                    @error('password')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="edit_password_confirmation" class="admin-prod-label">Confirm new password</label>
                    <input type="password" id="edit_password_confirmation" name="password_confirmation"
                        class="admin-prod-input" autocomplete="new-password">
                </div>
                <div class="admin-prod-form-footer !mt-0">
                    <button type="submit" class="admin-prod-btn-primary px-6">Save changes</button>
                </div>
            </form>
        </div>

        <div class="admin-clay-panel overflow-hidden mb-6">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Addresses &amp; locations</h2>
                <p class="admin-prod-form-hint">Registered locations for this dealer.</p>
            </div>
            <div class="admin-prod-form-body">
                @if($user->addresses->isEmpty())
                    <p class="admin-prod-muted">No addresses registered yet.</p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($user->addresses as $address)
                            <div class="admin-prod-address-card">
                                <div class="mb-3 flex flex-wrap gap-2">
                                    <span class="admin-prod-tag">{{ $address->type }}</span>
                                    @if($address->is_default)
                                        <span class="admin-prod-tag admin-prod-tag--accent">Default</span>
                                    @endif
                                </div>
                                <p class="text-sm font-bold text-[#232f3e]">{{ $address->address }}</p>
                                <p class="text-sm text-slate-600 mt-1">{{ $address->city }}, {{ $address->state }}
                                    {{ $address->zip }}</p>
                                <p class="text-sm text-slate-600">{{ $address->country }}</p>

                                @if($address->latitude && $address->longitude)
                                    <div id="map-{{ $address->id }}" class="admin-prod-map-frame mt-4"></div>
                                    <div class="mt-2 text-xs text-slate-500">
                                        <span class="font-variant-numeric">Lat: {{ $address->latitude }}, Lng:
                                            {{ $address->longitude }}</span>
                                        <span class="mx-1 text-slate-300">|</span>
                                        <a href="https://www.google.com/maps/search/?api=1&query={{ $address->latitude }},{{ $address->longitude }}"
                                            target="_blank" rel="noopener noreferrer" class="admin-prod-link">
                                            Open in Google Maps
                                        </a>
                                    </div>
                                @else
                                    <div class="admin-prod-map-placeholder mt-4">No map location provided</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="admin-prod-actions-bar mb-6">
            @if($user->status === 'pending')
                <form action="{{ route('admin.dealers.approve', $user->id) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="admin-prod-btn-primary admin-prod-btn-primary--success">
                        Approve dealer
                    </button>
                </form>
                <form action="{{ route('admin.dealers.reject', $user->id) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="admin-prod-btn-primary admin-prod-btn-primary--danger">
                        Reject
                    </button>
                </form>
            @elseif($user->status === 'active')
                <form action="{{ route('admin.dealers.reject', $user->id) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="admin-prod-btn-primary admin-prod-btn-primary--danger">
                        Suspend account
                    </button>
                </form>
            @elseif($user->status === 'suspended')
                <form action="{{ route('admin.dealers.approve', $user->id) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="admin-prod-btn-primary admin-prod-btn-primary--success">
                        Re-activate account
                    </button>
                </form>
            @endif
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var addresses = @json($user->addresses);

                addresses.forEach(function (address) {
                    if (address.latitude && address.longitude) {
                        var mapId = 'map-' + address.id;
                        var lat = parseFloat(address.latitude);
                        var lng = parseFloat(address.longitude);

                        var map = L.map(mapId, {
                            center: [lat, lng],
                            zoom: 15,
                            dragging: true,
                            touchZoom: true,
                            scrollWheelZoom: true,
                            doubleClickZoom: true,
                            boxZoom: true,
                            zoomControl: true
                        });

                        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                        }).addTo(map);

                        L.marker([lat, lng]).addTo(map);
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
