/**
 * Web portal notifications: inbox polling + browser push permission.
 */
export function initWebNotifications() {
    const bell = document.getElementById('optic-notification-bell');
    if (!bell || bell.dataset.initialized === '1') {
        return;
    }
    bell.dataset.initialized = '1';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const listUrl = bell.dataset.listUrl;
    const countUrl = bell.dataset.countUrl;
    const readAllUrl = bell.dataset.readAllUrl;
    const readBase = bell.dataset.readBase;
    let items = [];
    let unread = 0;
    let open = false;
    let pollTimer = null;
    let lastSeenIds = new Set();

    const panel = document.getElementById('optic-notification-panel');
    const badge = document.getElementById('optic-notification-badge');
    const listEl = document.getElementById('optic-notification-list');
    const permissionBtn = document.getElementById('optic-notification-permission-btn');
    const emptyEl = document.getElementById('optic-notification-empty');

    async function fetchJson(url, options = {}) {
        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.method && options.method !== 'GET' ? { 'X-CSRF-TOKEN': csrf } : {}),
                ...options.headers,
            },
            ...options,
        });
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }
        return res.json();
    }

    function renderBadge() {
        if (!badge) return;
        if (unread > 0) {
            badge.textContent = unread > 99 ? '99+' : String(unread);
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function renderList() {
        if (!listEl) return;
        listEl.innerHTML = '';
        if (!items.length) {
            emptyEl?.classList.remove('hidden');
            return;
        }
        emptyEl?.classList.add('hidden');
        items.forEach((item) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `w-full text-left px-4 py-3 border-b border-slate-100 hover:bg-slate-50 transition ${item.read_at ? 'opacity-75' : 'bg-orange-50/40'}`;
            btn.innerHTML = `
                <div class="text-sm font-semibold text-slate-800">${escapeHtml(item.title || 'Notification')}</div>
                <div class="text-xs text-slate-600 mt-1">${escapeHtml(item.body || '')}</div>
                <div class="text-[10px] text-slate-400 mt-1">${formatWhen(item.created_at)}</div>
            `;
            btn.addEventListener('click', () => openItem(item));
            listEl.appendChild(btn);
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    function formatWhen(iso) {
        if (!iso) return '';
        try {
            return new Date(iso).toLocaleString();
        } catch {
            return '';
        }
    }

    async function openItem(item) {
        if (!item.read_at) {
            try {
                await fetchJson(`${readBase}/${item.id}/read`, { method: 'POST' });
            } catch (_) {}
        }
        open = false;
        panel?.classList.add('hidden');
        await refresh();
        const url = item.web_url || item.route;
        if (url && url.startsWith('/')) {
            window.location.href = url;
        } else if (url) {
            window.location.href = url;
        }
    }

    function maybeDesktopAlert(newItems) {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }
        newItems.forEach((item) => {
            if (lastSeenIds.has(item.id)) return;
            lastSeenIds.add(item.id);
            if (document.visibilityState === 'visible') return;
            const n = new Notification(item.title || 'Optic', {
                body: item.body || '',
                icon: '/assets/app-icon.png',
            });
            n.onclick = () => {
                window.focus();
                openItem(item);
            };
        });
    }

    async function refresh() {
        try {
            const [listPayload, countPayload] = await Promise.all([
                fetchJson(listUrl),
                fetchJson(countUrl),
            ]);
            const nextItems = listPayload.data ?? [];
            const newUnread = countPayload.data?.unread_count ?? 0;
            const freshUnread = nextItems.filter((i) => !i.read_at && !lastSeenIds.has(i.id));
            maybeDesktopAlert(freshUnread);
            nextItems.forEach((i) => lastSeenIds.add(i.id));
            items = nextItems;
            unread = newUnread;
            renderBadge();
            renderList();
        } catch (e) {
            console.warn('Notification refresh failed', e);
        }
    }

    async function requestBrowserPermission() {
        if (!('Notification' in window)) {
            alert('This browser does not support desktop notifications.');
            return;
        }
        const result = await Notification.requestPermission();
        if (permissionBtn) {
            permissionBtn.classList.toggle('hidden', result === 'granted');
        }
        if (result === 'granted') {
            await refresh();
        }
    }

    bell.addEventListener('click', (e) => {
        e.stopPropagation();
        open = !open;
        panel?.classList.toggle('hidden', !open);
        if (open) refresh();
    });

    document.addEventListener('click', () => {
        open = false;
        panel?.classList.add('hidden');
    });

    panel?.addEventListener('click', (e) => e.stopPropagation());

    document.getElementById('optic-notification-mark-all')?.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            await fetchJson(readAllUrl, { method: 'POST' });
            await refresh();
        } catch (_) {}
    });

    permissionBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        requestBrowserPermission();
    });

    if ('Notification' in window && Notification.permission === 'default' && permissionBtn) {
        permissionBtn.classList.remove('hidden');
    }

    refresh();
    pollTimer = window.setInterval(refresh, 60000);
    window.addEventListener('beforeunload', () => {
        if (pollTimer) window.clearInterval(pollTimer);
    });
}
