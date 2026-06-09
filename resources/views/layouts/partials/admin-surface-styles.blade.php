<style type="text/css">
    @theme {
        --color-brand-black: #232f3e;
        --color-brand-dark: #19212c;
        --color-brand-orange: #fa8900;
        --color-brand-yellow: #febd69;
        --color-clay-canvas-from: #dce3ee;
        --color-clay-canvas-via: #e8edf5;
        --color-clay-canvas-to: #d4dce8;
        --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
    }

    [x-cloak] {
        display: none !important;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.35);
        border-radius: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.45);
        border-radius: 6px;
        box-shadow: inset 1px 1px 2px rgba(255, 255, 255, 0.5);
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(100, 116, 139, 0.5);
    }

    /* Clay panels (mirrors resources/css/app.css — ensures admin works without Vite) */
    .admin-clay-panel {
        border-radius: 1.5rem;
        background: linear-gradient(155deg,
                rgba(255, 255, 255, 0.94) 0%,
                rgba(248, 250, 252, 0.9) 45%,
                rgba(241, 245, 249, 0.88) 100%);
        border: 1px solid rgba(255, 255, 255, 0.85);
        box-shadow:
            10px 12px 24px rgba(163, 177, 198, 0.28),
            -6px -8px 20px rgba(255, 255, 255, 0.9),
            inset 2px 2px 5px rgba(255, 255, 255, 0.85),
            inset -2px -3px 8px rgba(148, 163, 184, 0.06);
    }

    .admin-clay-panel-interactive {
        border-radius: 1.5rem;
        background: linear-gradient(155deg,
                rgba(255, 255, 255, 0.96) 0%,
                rgba(248, 250, 252, 0.92) 100%);
        border: 1px solid rgba(255, 255, 255, 0.9);
        box-shadow:
            8px 10px 22px rgba(163, 177, 198, 0.26),
            -5px -6px 18px rgba(255, 255, 255, 0.88),
            inset 2px 2px 4px rgba(255, 255, 255, 0.8),
            inset -2px -2px 6px rgba(148, 163, 184, 0.05);
        transition:
            transform 0.2s ease,
            box-shadow 0.2s ease;
    }

    .admin-clay-panel-interactive:hover {
        transform: translateY(-3px);
        box-shadow:
            14px 16px 32px rgba(163, 177, 198, 0.32),
            -6px -8px 22px rgba(255, 255, 255, 0.95),
            inset 2px 2px 5px rgba(255, 255, 255, 0.9),
            inset -2px -3px 8px rgba(148, 163, 184, 0.07);
    }

    .admin-clay-inset {
        border-radius: 1rem;
        background: linear-gradient(165deg, rgba(226, 232, 240, 0.45), rgba(248, 250, 252, 0.65));
        box-shadow:
            inset 4px 4px 10px rgba(163, 177, 198, 0.2),
            inset -3px -3px 8px rgba(255, 255, 255, 0.85);
        border: 1px solid rgba(255, 255, 255, 0.5);
    }

    :root {
        --portal-sidebar-width: 18rem;
        --portal-header-offset: 7.75rem;
    }

    @media (min-width: 640px) {
        :root {
            --portal-header-offset: 8rem;
        }
    }

    /* Sidebar shell */
    .admin-sidebar {
        position: fixed;
        top: var(--portal-header-offset);
        left: 0;
        z-index: 50;
        width: var(--portal-sidebar-width);
        height: calc(100vh - var(--portal-header-offset));
        display: flex;
        flex-direction: column;
        overflow: hidden;
        flex-shrink: 0;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        border-radius: 0 1.75rem 1.75rem 0;
        border: 1px solid rgba(255, 255, 255, 0.78);
        border-left: none;
        background: linear-gradient(180deg,
                rgba(255, 255, 255, 0.97) 0%,
                rgba(248, 250, 252, 0.94) 42%,
                rgba(241, 245, 249, 0.9) 100%);
        box-shadow:
            10px 14px 32px rgba(163, 177, 198, 0.24),
            inset 2px 0 10px rgba(255, 255, 255, 0.7),
            inset 0 1px 0 rgba(255, 255, 255, 0.95);
    }

    .admin-sidebar.is-open,
    @media (min-width: 1024px) {
        .admin-sidebar {
            transform: translateX(0);
        }
    }

    @media (min-width: 1024px) {
        .admin-sidebar {
            left: 0.75rem;
            height: calc(100vh - var(--portal-header-offset) - 1rem);
            border-radius: 1.75rem;
            border-left: 1px solid rgba(255, 255, 255, 0.78);
        }
    }

    .admin-sidebar-mobile-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.65);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.55), transparent);
    }

    .admin-sidebar-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1.15rem 0.85rem;
        border-bottom: 1px solid rgba(226, 232, 240, 0.55);
    }

    .admin-sidebar-head-label {
        font-size: 0.6875rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #fa8900;
    }

    .admin-sidebar-head-hint {
        font-size: 0.75rem;
        font-weight: 500;
        color: rgb(100 116 139);
    }

    .admin-sidebar-nav {
        flex: 1;
        overflow-y: auto;
        padding: 1rem 0.85rem 1.25rem;
    }

    @media (min-width: 640px) {
        .admin-sidebar-nav {
            padding-left: 1rem;
            padding-right: 1rem;
        }
    }

    .admin-sidebar-section {
        margin-bottom: 1.35rem;
    }

    .admin-sidebar-section:last-child {
        margin-bottom: 0;
    }

    .portal-main-with-sidebar {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: calc(100vh - var(--portal-header-offset));
        overflow-y: auto;
    }

    @media (min-width: 1024px) {
        .portal-main-with-sidebar {
            padding-left: calc(var(--portal-sidebar-width) + 1.5rem);
        }
    }

    .admin-sidebar-overlay {
        position: fixed;
        inset: 0;
        z-index: 40;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(2px);
    }

    /* Sidebar: primary rows */
    .admin-sidebar-item {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.7rem;
        width: 100%;
        padding: 0.5rem 0.7rem 0.5rem 0.65rem;
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.25;
        color: rgb(51 65 85);
        border-radius: 0.9rem;
        border: 1px solid transparent;
        cursor: pointer;
        text-decoration: none;
        transition:
            background 0.2s ease,
            box-shadow 0.2s ease,
            color 0.2s ease,
            border-color 0.2s ease;
    }

    .admin-sidebar-item::before {
        content: '';
        position: absolute;
        left: 0.2rem;
        top: 0.45rem;
        bottom: 0.45rem;
        width: 3px;
        border-radius: 999px;
        background: transparent;
        transition: background 0.2s ease, box-shadow 0.2s ease;
    }

    .admin-sidebar-item:hover:not(.admin-sidebar-item-active) {
        background: rgba(255, 255, 255, 0.92);
        border-color: rgba(255, 255, 255, 0.8);
        color: rgb(15 23 42);
        box-shadow:
            3px 4px 12px rgba(163, 177, 198, 0.16),
            inset 0 0 0 1px rgba(255, 255, 255, 0.65);
    }

    .admin-sidebar-item-active {
        background: linear-gradient(135deg, rgba(250, 137, 0, 0.14), rgba(255, 255, 255, 0.96));
        border-color: rgba(250, 137, 0, 0.22);
        color: rgb(35 47 62);
        font-weight: 600;
        box-shadow:
            4px 6px 14px rgba(250, 137, 0, 0.1),
            inset 0 0 0 1px rgba(255, 255, 255, 0.55);
    }

    .admin-sidebar-item-active::before {
        background: #fa8900;
        box-shadow: 0 0 10px rgba(250, 137, 0, 0.35);
    }

    .admin-sidebar-item > svg,
    .admin-sidebar-item-leading > svg {
        flex-shrink: 0;
        width: 1.15rem;
        height: 1.15rem;
        padding: 0.42rem;
        box-sizing: content-box;
        border-radius: 0.7rem;
        color: rgb(100 116 139);
        background: rgba(255, 255, 255, 0.72);
        box-shadow:
            inset 1px 1px 3px rgba(255, 255, 255, 0.9),
            2px 2px 6px rgba(163, 177, 198, 0.12);
        transition:
            color 0.2s ease,
            background 0.2s ease,
            box-shadow 0.2s ease;
    }

    .admin-sidebar-item:hover:not(.admin-sidebar-item-active) > svg,
    .admin-sidebar-item:hover:not(.admin-sidebar-item-active) .admin-sidebar-item-leading > svg {
        color: rgb(71 85 105);
        background: rgba(255, 255, 255, 0.95);
    }

    .admin-sidebar-item-active > svg,
    .admin-sidebar-item-active .admin-sidebar-item-leading > svg {
        color: #fa8900;
        background: linear-gradient(145deg, rgba(250, 137, 0, 0.18), rgba(255, 255, 255, 0.95));
        box-shadow:
            inset 1px 1px 2px rgba(255, 255, 255, 0.85),
            2px 3px 8px rgba(250, 137, 0, 0.15);
    }

    .admin-sidebar-item:focus-visible,
    .admin-sidebar-sublink:focus-visible,
    .admin-sidebar-footer-btn:focus-visible {
        outline: 2px solid rgba(250, 137, 0, 0.45);
        outline-offset: 2px;
    }

    .admin-sidebar-group-btn {
        justify-content: space-between;
        text-align: left;
    }

    .admin-sidebar-item-leading {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        min-width: 0;
    }

    .admin-sidebar-chevron {
        flex-shrink: 0;
        width: 1rem;
        height: 1rem;
        color: rgb(148 163 184);
        transition:
            color 0.2s ease,
            transform 0.2s ease;
    }

    .admin-sidebar-item-active .admin-sidebar-chevron {
        color: #fa8900;
    }

    .admin-sidebar-item:hover:not(.admin-sidebar-item-active) .admin-sidebar-chevron {
        color: rgb(100 116 139);
    }

    /* Sidebar: nested links */
    .admin-sidebar-subtree {
        margin-top: 0.35rem;
        margin-left: 0.55rem;
        padding-left: 0.85rem;
        border-left: 2px solid rgba(250, 137, 0, 0.14);
    }

    .admin-sidebar-sublink {
        display: block;
        padding: 0.42rem 0.6rem 0.42rem 0.7rem;
        font-size: 0.8125rem;
        font-weight: 500;
        color: rgb(71 85 105);
        border-radius: 0.65rem;
        text-decoration: none;
        cursor: pointer;
        transition:
            background 0.2s ease,
            color 0.2s ease,
            box-shadow 0.2s ease;
    }

    .admin-sidebar-sublink:hover:not(.admin-sidebar-sublink-active) {
        background: rgba(255, 255, 255, 0.78);
        color: rgb(15 23 42);
    }

    .admin-sidebar-sublink-active {
        background: rgba(255, 255, 255, 0.96);
        color: rgb(35 47 62);
        font-weight: 600;
        box-shadow:
            inset 2px 0 0 #fa8900,
            2px 2px 8px rgba(250, 137, 0, 0.07);
    }

    .admin-sidebar-section-title {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0 0.45rem;
        margin-bottom: 0.55rem;
        font-size: 0.625rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: rgb(100 116 139);
    }

    .admin-sidebar-section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, rgba(148, 163, 184, 0.35), transparent);
    }

    /* Sidebar footer */
    .admin-sidebar-footer {
        flex-shrink: 0;
        padding: 0.85rem 1rem 1rem;
        border-top: 1px solid rgba(226, 232, 240, 0.55);
        background: linear-gradient(180deg, transparent, rgba(255, 255, 255, 0.45));
    }

    .admin-sidebar-footer-btn {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.55rem 0.5rem;
        border-radius: 1rem;
        border: 1px solid transparent;
        background: transparent;
        transition:
            background 0.2s ease,
            border-color 0.2s ease,
            box-shadow 0.2s ease;
    }

    .admin-sidebar-footer-btn:hover {
        background: rgba(255, 255, 255, 0.88);
        border-color: rgba(255, 255, 255, 0.75);
        box-shadow: 2px 3px 10px rgba(163, 177, 198, 0.14);
    }

    .admin-sidebar-footer-avatar {
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(145deg, #fa8900, #e07800);
        box-shadow:
            inset 1px 2px 4px rgba(255, 255, 255, 0.35),
            2px 3px 10px rgba(250, 137, 0, 0.28);
        flex-shrink: 0;
    }

    .admin-sidebar-footer-chevron {
        width: 1rem;
        height: 1rem;
        color: rgb(148 163 184);
        transition: transform 0.2s ease, color 0.2s ease;
        flex-shrink: 0;
    }

    .admin-sidebar-footer-menu {
        position: absolute;
        bottom: calc(100% + 0.45rem);
        left: 0;
        right: 0;
        padding: 0.35rem;
        border-radius: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.9);
        background: linear-gradient(165deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
        box-shadow:
            10px 14px 28px rgba(163, 177, 198, 0.28),
            inset 1px 1px 2px rgba(255, 255, 255, 0.85);
        z-index: 60;
    }

    .admin-sidebar-footer-menu-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        width: 100%;
        padding: 0.55rem 0.7rem;
        font-size: 0.8125rem;
        font-weight: 500;
        color: rgb(51 65 85);
        border-radius: 0.75rem;
        border: none;
        background: transparent;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.15s ease, color 0.15s ease;
    }

    .admin-sidebar-footer-menu-item:hover {
        background: rgba(255, 255, 255, 0.9);
        color: rgb(15 23 42);
    }

    .admin-sidebar-footer-menu-item--danger:hover {
        color: rgb(220 38 38);
        background: rgba(254, 242, 242, 0.9);
    }

    @media (prefers-reduced-motion: reduce) {
        .admin-sidebar,
        .admin-sidebar-item,
        .admin-sidebar-sublink,
        .admin-sidebar-chevron,
        .admin-sidebar-footer-btn,
        .admin-sidebar-footer-chevron {
            transition: none;
        }
    }
</style>
