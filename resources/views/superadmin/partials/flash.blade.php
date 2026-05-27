@if (session('success'))
    <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ session('error') }}</div>
@endif
