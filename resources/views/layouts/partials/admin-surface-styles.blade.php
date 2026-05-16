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

    /* Sidebar: primary rows */
    .admin-sidebar-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.55rem 0.85rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: rgb(51 65 85);
        border-radius: 0.85rem;
        border: 1px solid transparent;
        transition:
            background 0.2s ease,
            box-shadow 0.2s ease,
            color 0.2s ease,
            transform 0.2s ease,
            border-color 0.2s ease;
    }

    .admin-sidebar-item:hover:not(.admin-sidebar-item-active) {
        background: rgba(255, 255, 255, 0.88);
        border-color: rgba(255, 255, 255, 0.65);
        box-shadow:
            4px 5px 14px rgba(163, 177, 198, 0.2),
            -2px -2px 10px rgba(255, 255, 255, 0.95),
            inset 0 0 0 1px rgba(255, 255, 255, 0.55);
        transform: translateX(3px);
        color: rgb(15 23 42);
    }

    .admin-sidebar-item-active {
        background: linear-gradient(135deg, rgba(250, 137, 0, 0.16), rgba(255, 255, 255, 0.94));
        border-color: rgba(250, 137, 0, 0.28);
        color: rgb(35 47 62);
        font-weight: 600;
        box-shadow:
            4px 6px 16px rgba(250, 137, 0, 0.14),
            -3px -3px 12px rgba(255, 255, 255, 0.98),
            inset 2px 2px 6px rgba(255, 255, 255, 0.8),
            inset 0 0 0 1px rgba(255, 255, 255, 0.45);
    }

    .admin-sidebar-item svg {
        flex-shrink: 0;
        width: 1.25rem;
        height: 1.25rem;
        color: rgb(148 163 184);
        transition: color 0.2s ease;
    }

    .admin-sidebar-item:hover:not(.admin-sidebar-item-active) svg {
        color: rgb(100 116 139);
    }

    .admin-sidebar-item-active svg {
        color: #fa8900;
    }

    .admin-sidebar-item:focus-visible {
        outline: 2px solid rgba(250, 137, 0, 0.45);
        outline-offset: 2px;
    }

    .admin-sidebar-group-btn {
        width: 100%;
        justify-content: space-between;
        text-align: left;
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
    .admin-sidebar-sublink {
        display: block;
        padding: 0.45rem 0.65rem 0.45rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 500;
        color: rgb(71 85 105);
        border-radius: 0.65rem;
        border-left: 3px solid transparent;
        transition:
            background 0.2s ease,
            color 0.2s ease,
            border-color 0.2s ease,
            transform 0.2s ease,
            box-shadow 0.2s ease;
    }

    .admin-sidebar-sublink:hover:not(.admin-sidebar-sublink-active) {
        background: rgba(255, 255, 255, 0.72);
        color: rgb(15 23 42);
        border-left-color: rgba(250, 137, 0, 0.35);
        transform: translateX(2px);
        box-shadow: 2px 2px 8px rgba(163, 177, 198, 0.12);
    }

    .admin-sidebar-sublink-active {
        background: rgba(255, 255, 255, 0.95);
        color: rgb(35 47 62);
        font-weight: 600;
        border-left-color: #fa8900;
        box-shadow:
            inset 0 1px 2px rgba(255, 255, 255, 0.9),
            2px 2px 10px rgba(250, 137, 0, 0.08);
    }

    .admin-sidebar-sublink:focus-visible {
        outline: 2px solid rgba(250, 137, 0, 0.4);
        outline-offset: 1px;
    }

    .admin-sidebar-section-title {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: rgb(100 116 139);
        margin-bottom: 0.5rem;
    }
</style>
