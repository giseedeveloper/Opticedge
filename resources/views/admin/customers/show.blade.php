<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Users</p>
                <h1 class="admin-prod-title">{{ $user->name }}</h1>
                <p class="admin-prod-subtitle">User account details.</p>
            </div>
            <a href="{{ route('admin.customers.index', request()->only('role')) }}" class="admin-prod-back shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to all users
            </a>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-6" role="status">{{ session('success') }}</div>
        @endif

        <div class="admin-clay-panel overflow-hidden mb-6">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Profile</h2>
            </div>
            <dl class="admin-prod-detail-body">
                <div class="admin-prod-detail-row">
                    <dt class="admin-prod-detail-dt">Name</dt>
                    <dd class="admin-prod-detail-dd font-bold">{{ $user->name }}</dd>
                </div>
                <div class="admin-prod-detail-row">
                    <dt class="admin-prod-detail-dt">Email</dt>
                    <dd class="admin-prod-detail-dd">{{ $user->email }}</dd>
                </div>
                @if($user->phone)
                    <div class="admin-prod-detail-row">
                        <dt class="admin-prod-detail-dt">Phone</dt>
                        <dd class="admin-prod-detail-dd">{{ $user->phone }}</dd>
                    </div>
                @endif
                <div class="admin-prod-detail-row">
                    <dt class="admin-prod-detail-dt">Role</dt>
                    <dd class="admin-prod-detail-dd">{{ match ($user->role) {
                        'regional_manager' => 'Regional manager',
                        'teamleader' => 'Team leader',
                        default => ucfirst($user->role ?? 'customer'),
                    } }}</dd>
                </div>
                <div class="admin-prod-detail-row">
                    <dt class="admin-prod-detail-dt">Status</dt>
                    <dd class="admin-prod-detail-dd">{{ ucfirst($user->status ?? 'active') }}</dd>
                </div>
                @if($user->listRegionName())
                    <div class="admin-prod-detail-row">
                        <dt class="admin-prod-detail-dt">Region</dt>
                        <dd class="admin-prod-detail-dd">{{ $user->listRegionName() }}</dd>
                    </div>
                @endif
                @if($user->listBranchName())
                    <div class="admin-prod-detail-row">
                        <dt class="admin-prod-detail-dt">Branch</dt>
                        <dd class="admin-prod-detail-dd">{{ $user->listBranchName() }}</dd>
                    </div>
                @endif
                @if($user->regionalManager)
                    <div class="admin-prod-detail-row">
                        <dt class="admin-prod-detail-dt">Regional manager</dt>
                        <dd class="admin-prod-detail-dd">{{ $user->regionalManager->name }}</dd>
                    </div>
                @endif
                @if($user->teamLeader)
                    <div class="admin-prod-detail-row">
                        <dt class="admin-prod-detail-dt">Team leader</dt>
                        <dd class="admin-prod-detail-dd">{{ $user->teamLeader->name }}</dd>
                    </div>
                @endif
                <div class="admin-prod-detail-row">
                    <dt class="admin-prod-detail-dt">Joined</dt>
                    <dd class="admin-prod-detail-dd">{{ $user->created_at->format('M j, Y') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</x-admin-layout>
