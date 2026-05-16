<x-regional-manager-layout title="Profile">
    <div class="admin-prod-page !pt-4 sm:!pt-6">
        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Account</p>
                <h1 class="admin-prod-title">Profile</h1>
                <p class="admin-prod-subtitle">Update your name and email, change your password, or close your account.</p>
            </div>
        </div>

        <div class="space-y-6 max-w-3xl">
            <div class="admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head border-b border-white/60">
                    <h2 class="admin-prod-form-title">Profile information</h2>
                </div>
                <div class="admin-prod-form-body">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head border-b border-white/60">
                    <h2 class="admin-prod-form-title">Password</h2>
                </div>
                <div class="admin-prod-form-body">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head border-b border-white/60">
                    <h2 class="admin-prod-form-title">Delete account</h2>
                </div>
                <div class="admin-prod-form-body">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>
</x-regional-manager-layout>
