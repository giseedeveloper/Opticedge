@props(['user'])

<div class="admin-user-actions-collapse__section">
    <details class="admin-reset-password-collapse">
        <summary class="admin-reset-password-collapse__toggle admin-user-actions-collapse__label">
            Reset Password
        </summary>
        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}"
            class="admin-reset-password-collapse__form mt-1 flex flex-wrap items-center justify-end gap-2">
            @csrf
            <input type="password" name="password" required minlength="8"
                placeholder="New password" class="admin-prod-input w-36 py-1.5 text-sm">
            <input type="password" name="password_confirmation" required minlength="8"
                placeholder="Confirm" class="admin-prod-input w-32 py-1.5 text-sm">
            <button type="submit" class="admin-prod-link whitespace-nowrap text-sm">Save</button>
        </form>
    </details>
</div>
