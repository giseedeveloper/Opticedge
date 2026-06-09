<td {{ $attributes->merge(['class' => 'admin-prod-cell-actions']) }}>
    <details class="admin-user-actions-collapse">
        <summary class="admin-user-actions-collapse__toggle">
            <span>Actions</span>
            <svg class="admin-user-actions-collapse__chevron" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </summary>
        <div class="admin-user-actions-collapse__panel">
            {{ $slot }}
        </div>
    </details>
</td>
