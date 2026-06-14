<div class="relative" id="optic-notification-root">
    <button type="button" id="optic-notification-bell"
        data-list-url="{{ route('web.notifications.index') }}"
        data-count-url="{{ route('web.notifications.unread-count') }}"
        data-read-all-url="{{ route('web.notifications.read-all') }}"
        data-read-base="{{ url('app/notifications') }}"
        class="{{ $buttonClass ?? 'p-2 rounded-xl admin-clay-inset text-slate-600 hover:text-[#232f3e] transition-all duration-200 relative' }}"
        aria-label="Notifications">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <span id="optic-notification-badge"
            class="hidden absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">0</span>
    </button>

    <div id="optic-notification-panel"
        class="hidden absolute right-0 mt-2 w-80 sm:w-96 max-h-[70vh] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl z-[200]">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <span class="text-sm font-semibold text-slate-800">Notifications</span>
            <div class="flex items-center gap-2">
                <button type="button" id="optic-notification-permission-btn"
                    class="hidden text-xs font-medium text-[#fa8900] hover:underline">Enable alerts</button>
                <button type="button" id="optic-notification-mark-all"
                    class="text-xs font-medium text-slate-500 hover:text-slate-800">Mark all read</button>
            </div>
        </div>
        <div id="optic-notification-empty" class="px-4 py-8 text-center text-sm text-slate-500 hidden">No notifications yet.</div>
        <div id="optic-notification-list" class="overflow-y-auto max-h-[55vh]"></div>
    </div>
</div>
