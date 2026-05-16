<x-team-leader-layout title="Add address">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <style>
        .leaflet-container {
            z-index: 0;
        }
    </style>

    <div class="admin-prod-page !pt-4 sm:!pt-6">
        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Account</p>
                <h1 class="admin-prod-title">Add address</h1>
                <p class="admin-prod-subtitle">Save a shipping address for checkout.</p>
            </div>
        </div>

        <div class="admin-clay-panel max-w-3xl overflow-hidden">
            <form action="{{ route('addresses.store') }}" method="POST" class="admin-prod-form-body space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-6">
                        <label for="country" class="admin-prod-label">Country / region</label>
                        <select id="country" name="country" required class="admin-prod-input">
                            <option value="Tanzania" selected>Tanzania</option>
                            <option value="United States">United States</option>
                            <option value="Kenya">Kenya</option>
                            <option value="Uganda">Uganda</option>
                        </select>
                    </div>
                    <div class="sm:col-span-6">
                        <span class="admin-prod-label">Recipient</span>
                        <p class="mt-1 text-sm text-slate-600">{{ Auth::user()->name }}</p>
                    </div>
                    <div class="sm:col-span-6">
                        <label for="address" class="admin-prod-label">Street address</label>
                        <input type="text" name="address" id="address" required autocomplete="street-address"
                            placeholder="Street, P.O. box, company"
                            class="admin-prod-input" value="{{ old('address') }}">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="city" class="admin-prod-label">City</label>
                        <input type="text" name="city" id="city" required autocomplete="address-level2" class="admin-prod-input"
                            value="{{ old('city') }}">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="state" class="admin-prod-label">State / province</label>
                        <input type="text" name="state" id="state" autocomplete="address-level1" class="admin-prod-input"
                            value="{{ old('state') }}">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="zip" class="admin-prod-label">ZIP / postal code</label>
                        <input type="text" name="zip" id="zip" autocomplete="postal-code" class="admin-prod-input"
                            value="{{ old('zip') }}">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="type" class="admin-prod-label">Address type</label>
                        <select name="type" id="type" required class="admin-prod-input">
                            <option value="Home">Home</option>
                            <option value="Office">Office</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div>
                    <span class="admin-prod-label">Pin on map</span>
                    <div id="map" class="mt-2 h-64 w-full overflow-hidden rounded-xl border border-white/60"></div>
                    <p class="mt-1 text-xs text-slate-500">Drag the marker or click the map to set coordinates.</p>
                    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
                </div>

                <div class="admin-prod-form-footer !mt-0 flex flex-wrap gap-3">
                    <a href="{{ route('team-leader.addresses.index') }}" class="admin-prod-btn-ghost">Cancel</a>
                    <button type="submit" class="admin-prod-btn-primary">Save address</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var defaultLat = -6.7924;
                var defaultLng = 39.2083;
                var map = L.map('map').setView([defaultLat, defaultLng], 13);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                var marker = L.marker([defaultLat, defaultLng], {
                    draggable: true
                }).addTo(map);
                document.getElementById('latitude').value = defaultLat;
                document.getElementById('longitude').value = defaultLng;
                marker.on('dragend', function() {
                    var ll = marker.getLatLng();
                    document.getElementById('latitude').value = ll.lat;
                    document.getElementById('longitude').value = ll.lng;
                });
                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    document.getElementById('latitude').value = e.latlng.lat;
                    document.getElementById('longitude').value = e.latlng.lng;
                });
            });
        </script>
    @endpush
</x-team-leader-layout>
