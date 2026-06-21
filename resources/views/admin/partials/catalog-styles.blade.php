@once('admin-catalog-clay-styles')
    @push('styles')
        <style>
            .admin-prod-page {
                max-width: 1600px;
                margin-left: auto;
                margin-right: auto;
                padding: 2rem 1rem 2.5rem;
            }

            @media (min-width: 640px) {
                .admin-prod-page {
                    padding-left: 1.5rem;
                    padding-right: 1.5rem;
                }
            }

            @media (min-width: 1024px) {
                .admin-prod-page {
                    padding-left: 2rem;
                    padding-right: 2rem;
                }
            }

            .admin-prod-page--narrow {
                max-width: 42rem;
            }

            .admin-prod-page--wide {
                max-width: 64rem;
            }

            .admin-prod-toolbar {
                display: flex;
                flex-direction: column;
                gap: 1rem;
                margin-bottom: 1.5rem;
            }

            @media (min-width: 640px) {
                .admin-prod-toolbar {
                    flex-direction: row;
                    align-items: center;
                    justify-content: space-between;
                }
            }

            .admin-prod-eyebrow {
                font-size: 0.625rem;
                font-weight: 800;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                color: #94a3b8;
                margin-bottom: 0.25rem;
            }

            .admin-prod-title {
                font-size: 1.5rem;
                font-weight: 800;
                color: #232f3e;
                letter-spacing: -0.03em;
                line-height: 1.2;
                margin: 0;
            }

            .admin-prod-subtitle {
                margin-top: 0.35rem;
                font-size: 0.875rem;
                color: #64748b;
                line-height: 1.45;
            }

            .admin-prod-back {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                font-size: 0.8125rem;
                font-weight: 600;
                color: #64748b;
                transition: color 0.15s ease;
            }

            .admin-prod-back:hover {
                color: #232f3e;
            }

            .admin-prod-btn-primary {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.35rem;
                padding: 0.55rem 1.15rem;
                border-radius: 0.75rem;
                font-size: 0.8125rem;
                font-weight: 700;
                color: #fff;
                background: linear-gradient(145deg, #fa8900, #e07800);
                border: 1px solid rgba(255, 255, 255, 0.35);
                box-shadow:
                    4px 6px 16px rgba(250, 137, 0, 0.32),
                    inset 1px 1px 2px rgba(255, 255, 255, 0.35);
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }

            .admin-prod-btn-primary:hover {
                transform: translateY(-1px);
                box-shadow:
                    6px 8px 20px rgba(250, 137, 0, 0.38),
                    inset 1px 1px 2px rgba(255, 255, 255, 0.4);
            }

            .admin-prod-btn-ghost {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.5rem 1rem;
                border-radius: 0.75rem;
                font-size: 0.8125rem;
                font-weight: 600;
                color: #475569;
                background: rgba(255, 255, 255, 0.65);
                border: 1px solid rgba(255, 255, 255, 0.95);
                box-shadow: 2px 3px 8px rgba(163, 177, 198, 0.1);
                transition: color 0.15s, background 0.15s, transform 0.15s;
            }

            .admin-prod-btn-ghost:hover {
                color: #232f3e;
                background: rgba(255, 255, 255, 0.9);
                transform: translateY(-1px);
            }

            .admin-prod-alert {
                margin-bottom: 1rem;
                padding: 0.9rem 1.1rem;
                border-radius: 0.85rem;
                font-size: 0.875rem;
                font-weight: 500;
                border: 1px solid transparent;
            }

            .admin-prod-alert--success {
                color: #166534;
                background: linear-gradient(145deg, rgba(220, 252, 231, 0.95), rgba(255, 255, 255, 0.9));
                border-color: rgba(34, 197, 94, 0.25);
                box-shadow:
                    3px 4px 12px rgba(34, 197, 94, 0.08),
                    inset 1px 1px 2px rgba(255, 255, 255, 0.8);
            }

            .admin-prod-alert--error {
                color: #991b1b;
                background: linear-gradient(145deg, rgba(254, 226, 226, 0.95), rgba(255, 255, 255, 0.92));
                border-color: rgba(239, 68, 68, 0.22);
                box-shadow:
                    3px 4px 12px rgba(239, 68, 68, 0.08),
                    inset 1px 1px 2px rgba(255, 255, 255, 0.75);
            }

            .admin-prod-alert--warning {
                color: #92400e;
                background: linear-gradient(145deg, rgba(254, 243, 199, 0.65), rgba(255, 255, 255, 0.92));
                border-color: rgba(245, 158, 11, 0.35);
                box-shadow:
                    3px 4px 12px rgba(245, 158, 11, 0.08),
                    inset 1px 1px 2px rgba(255, 255, 255, 0.75);
            }

            .admin-prod-table-wrap {
                border-radius: 1rem;
                /* overflow-x for wide tables; do not use overflow:hidden — combined with
                   Tailwind overflow-x-auto it left overflow-y:hidden and blocked page scroll. */
                overflow-x: auto;
                overflow-y: visible;
                border: 1px solid rgba(255, 255, 255, 0.75);
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.9),
                    3px 4px 14px rgba(163, 177, 198, 0.1);
            }

            .admin-prod-table-wrap--flush {
                border-radius: 0;
                border: none;
                box-shadow: none;
            }

            .admin-prod-table-wrap table {
                width: 100%;
                border-collapse: collapse;
                text-align: left;
                font-size: 0.875rem;
            }

            .admin-prod-table-wrap thead tr {
                background: transparent;
            }

            .admin-prod-th {
                background: linear-gradient(180deg, #e8ecf2 0%, #dce2ea 45%, #cfd6e0 100%);
                color: #475569;
                font-size: 0.6875rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                padding: 0.95rem 1.15rem;
                border-bottom: 1px solid #aeb9c9;
                border-right: 1px solid rgba(255, 255, 255, 0.5);
                white-space: nowrap;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
            }

            .admin-prod-th:last-child {
                border-right: none;
            }

            .admin-prod-th--end {
                text-align: right;
            }

            .admin-prod-th--desc {
                min-width: 12rem;
                max-width: 28rem;
            }

            .admin-prod-th--index {
                width: 3rem;
            }

            .admin-prod-th--image {
                width: 4.5rem;
            }

            .admin-prod-sort-link {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                color: inherit;
                text-decoration: none;
                transition: color 0.15s ease;
            }

            .admin-prod-sort-link:hover {
                color: #232f3e;
            }

            .admin-prod-sort-link--active {
                color: #232f3e;
            }

            .admin-prod-sort-link__icon {
                display: inline-flex;
                width: 0.875rem;
                height: 0.875rem;
                opacity: 0.45;
            }

            .admin-prod-sort-link--active .admin-prod-sort-link__icon {
                opacity: 1;
                color: #c2410c;
            }

            .admin-prod-table-wrap tbody tr {
                transition: background 0.15s ease;
                border-bottom: 1px solid rgba(241, 245, 249, 0.9);
            }

            .admin-prod-table-wrap tbody tr:hover {
                background: rgba(255, 255, 255, 0.65);
            }

            .admin-prod-table-wrap tbody td {
                padding: 0.85rem 1.15rem;
                vertical-align: middle;
                color: #475569;
            }

            .admin-prod-thumb {
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 9999px;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(145deg, rgba(248, 250, 252, 0.95), rgba(255, 255, 255, 0.9));
                border: 1px solid rgba(255, 255, 255, 0.95);
                box-shadow:
                    inset 2px 2px 6px rgba(163, 177, 198, 0.12),
                    2px 2px 8px rgba(163, 177, 198, 0.08);
            }

            .admin-prod-thumb--tile {
                width: 3rem;
                height: 3rem;
                border-radius: 0.65rem;
            }

            .admin-prod-thumb--tile img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .admin-prod-count-pill {
                display: inline-flex;
                align-items: center;
                padding: 0.28rem 0.65rem;
                border-radius: 9999px;
                font-size: 0.6875rem;
                font-weight: 700;
                border: 1px solid rgba(255, 255, 255, 0.9);
                box-shadow:
                    2px 2px 6px rgba(163, 177, 198, 0.12),
                    inset 0 1px 1px rgba(255, 255, 255, 0.85);
            }

            .admin-prod-count-pill--neutral {
                background: linear-gradient(145deg, rgba(241, 245, 249, 0.95), rgba(255, 255, 255, 0.9));
                color: #475569;
                border-color: rgba(148, 163, 184, 0.25);
            }

            .admin-prod-count-pill--info {
                background: linear-gradient(145deg, rgba(219, 234, 254, 0.85), rgba(255, 255, 255, 0.95));
                color: #1d4ed8;
                border-color: rgba(59, 130, 246, 0.22);
            }

            .admin-prod-link {
                font-size: 0.8125rem;
                font-weight: 600;
                color: #c2410c;
                transition: color 0.15s;
            }

            .admin-prod-link:hover {
                color: #9a3412;
            }

            .admin-prod-table-wrap tbody a.admin-prod-link:hover {
                text-decoration: underline;
                text-underline-offset: 2px;
            }

            .admin-prod-link--danger {
                color: #dc2626;
            }

            .admin-prod-link--danger:hover {
                color: #b91c1c;
            }

            .admin-prod-cell-actions {
                text-align: right;
            }

            .admin-user-actions-collapse {
                width: 100%;
                min-width: 7rem;
            }

            .admin-user-actions-collapse__toggle {
                display: inline-flex;
                align-items: center;
                justify-content: flex-end;
                gap: 0.25rem;
                width: 100%;
                cursor: pointer;
                list-style: none;
                font-size: 0.8125rem;
                font-weight: 600;
                color: #fa8900;
                user-select: none;
            }

            .admin-user-actions-collapse__toggle::-webkit-details-marker {
                display: none;
            }

            .admin-user-actions-collapse__toggle:hover {
                color: #c2410c;
            }

            .admin-user-actions-collapse__chevron {
                width: 1rem;
                height: 1rem;
                flex-shrink: 0;
                transition: transform 0.15s ease;
            }

            .admin-user-actions-collapse[open] .admin-user-actions-collapse__chevron {
                transform: rotate(180deg);
            }

            .admin-user-actions-collapse__panel {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 0.5rem;
                min-width: 260px;
                margin-top: 0.5rem;
                padding-top: 0.5rem;
                border-top: 1px solid rgba(203, 213, 225, 0.7);
            }

            .admin-user-actions-collapse__label {
                width: 100%;
                font-size: 0.6875rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: #94a3b8;
                text-align: right;
            }

            .admin-user-actions-collapse__section {
                width: 100%;
            }

            .admin-reset-password-collapse {
                width: 100%;
            }

            .admin-reset-password-collapse__toggle {
                cursor: pointer;
                list-style: none;
            }

            .admin-reset-password-collapse__toggle::-webkit-details-marker {
                display: none;
            }

            .admin-reset-password-collapse__toggle:hover {
                color: #64748b;
            }

            .admin-prod-actions {
                display: inline-flex;
                justify-content: flex-end;
                align-items: center;
                gap: 1rem;
            }

            .admin-prod-pagination {
                padding: 1rem 1.15rem;
                border-top: 1px solid rgba(255, 255, 255, 0.75);
                background: linear-gradient(180deg, rgba(248, 250, 252, 0.45), rgba(255, 255, 255, 0.25));
            }

            .admin-prod-form-head {
                padding: 1.1rem 1.5rem;
                background: linear-gradient(165deg, rgba(255, 255, 255, 0.65), rgba(241, 245, 249, 0.45));
                border-bottom: 1px solid rgba(203, 213, 225, 0.85);
                box-shadow: inset 0 -2px 8px rgba(148, 163, 184, 0.06);
            }

            .admin-prod-form-head-row {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }

            @media (min-width: 640px) {
                .admin-prod-form-head-row {
                    flex-direction: row;
                    align-items: center;
                    justify-content: space-between;
                }
            }

            .admin-prod-form-title {
                font-size: 1.0625rem;
                font-weight: 700;
                color: #232f3e;
            }

            .admin-prod-form-hint {
                font-size: 0.75rem;
                color: #64748b;
                margin-top: 0.2rem;
            }

            .admin-prod-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.35rem 0.75rem;
                border-radius: 9999px;
                font-size: 0.65rem;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                background: linear-gradient(145deg, rgba(59, 130, 246, 0.12), rgba(255, 255, 255, 0.95));
                border: 1px solid rgba(59, 130, 246, 0.22);
                color: #1d4ed8;
                box-shadow: 2px 2px 8px rgba(59, 130, 246, 0.08), inset 0 1px 1px rgba(255, 255, 255, 0.9);
            }

            .admin-prod-label {
                display: block;
                font-size: 0.8125rem;
                font-weight: 700;
                color: #334155;
                margin-bottom: 0.45rem;
            }

            .admin-prod-input,
            .admin-prod-select,
            .admin-prod-textarea {
                display: block;
                width: 100%;
                border-radius: 0.75rem;
                border: 1px solid rgba(148, 163, 184, 0.72);
                background: linear-gradient(165deg, rgba(248, 250, 252, 0.98), rgba(255, 255, 255, 0.99));
                box-shadow:
                    inset 2px 2px 6px rgba(163, 177, 198, 0.08),
                    inset -1px -1px 4px rgba(255, 255, 255, 0.9),
                    0 1px 2px rgba(15, 23, 42, 0.04);
                padding: 0.65rem 0.85rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: #0f172a;
                transition:
                    border-color 0.15s ease,
                    box-shadow 0.15s ease;
            }

            .admin-prod-input::placeholder,
            .admin-prod-textarea::placeholder {
                color: #94a3b8;
            }

            .admin-prod-input:hover,
            .admin-prod-select:hover,
            .admin-prod-textarea:hover {
                border-color: rgba(100, 116, 139, 0.55);
            }

            .admin-prod-input:focus,
            .admin-prod-select:focus,
            .admin-prod-textarea:focus {
                outline: none;
                border-color: rgba(250, 137, 0, 0.85);
                box-shadow:
                    inset 2px 2px 6px rgba(163, 177, 198, 0.06),
                    0 0 0 3px rgba(250, 137, 0, 0.2);
            }

            .admin-prod-input:disabled,
            .admin-prod-select:disabled,
            .admin-prod-textarea:disabled {
                opacity: 0.92;
                cursor: not-allowed;
                background: linear-gradient(165deg, rgba(241, 245, 249, 0.95), rgba(248, 250, 252, 0.98));
                border-color: rgba(148, 163, 184, 0.5);
            }

            .admin-prod-readonly-box {
                width: 100%;
                border-radius: 0.75rem;
                border: 1px solid rgba(148, 163, 184, 0.55);
                background: linear-gradient(165deg, rgba(241, 245, 249, 0.9), rgba(248, 250, 252, 0.95));
                box-shadow: inset 2px 2px 6px rgba(163, 177, 198, 0.06);
                padding: 0.65rem 0.85rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: #334155;
            }

            .admin-prod-textarea {
                resize: vertical;
                min-height: 6rem;
            }

            .admin-prod-file {
                width: 100%;
                font-size: 0.8125rem;
                color: #64748b;
                border-radius: 0.75rem;
                border: 1px dashed rgba(148, 163, 184, 0.75);
                background: rgba(255, 255, 255, 0.65);
                padding: 0.5rem;
                box-shadow:
                    inset 2px 2px 6px rgba(163, 177, 198, 0.08),
                    0 0 0 1px rgba(255, 255, 255, 0.6);
            }

            .admin-prod-file::file-selector-button {
                margin-right: 0.75rem;
                padding: 0.45rem 1rem;
                border-radius: 9999px;
                border: 0;
                font-size: 0.75rem;
                font-weight: 700;
                background: linear-gradient(145deg, rgba(250, 137, 0, 0.18), rgba(255, 255, 255, 0.95));
                color: #c2410c;
                cursor: pointer;
                box-shadow: 2px 2px 8px rgba(250, 137, 0, 0.12);
            }

            .admin-prod-form-body {
                padding: 1.5rem;
                background: linear-gradient(180deg, rgba(248, 250, 252, 0.35), rgba(255, 255, 255, 0.12));
            }

            .admin-prod-form-footer {
                margin-top: 1.5rem;
                padding-top: 1.25rem;
                border-top: 1px solid rgba(203, 213, 225, 0.75);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: flex-end;
                gap: 0.75rem;
            }

            .admin-prod-img-grid img {
                border-radius: 0.5rem;
                border: 1px solid rgba(255, 255, 255, 0.9);
                box-shadow: 2px 3px 10px rgba(163, 177, 198, 0.12);
            }

            .admin-prod-preview-sm {
                width: 6rem;
                height: 6rem;
                object-fit: cover;
                border-radius: 0.75rem;
                border: 1px solid rgba(255, 255, 255, 0.95);
                box-shadow:
                    3px 4px 12px rgba(163, 177, 198, 0.15),
                    inset 0 1px 1px rgba(255, 255, 255, 0.8);
            }

            .admin-prod-status {
                display: inline-flex;
                padding: 0.25rem 0.5rem;
                border-radius: 0.5rem;
                font-size: 0.65rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                border: 1px solid transparent;
            }

            .admin-prod-status--ok {
                background: rgba(220, 252, 231, 0.9);
                color: #166534;
                border-color: rgba(34, 197, 94, 0.25);
            }

            .admin-prod-status--sold {
                background: rgba(241, 245, 249, 0.95);
                color: #475569;
                border-color: rgba(148, 163, 184, 0.35);
            }

            /* Customers / users list */
            .admin-prod-filter-row {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                align-items: center;
            }

            .admin-prod-filter-tab {
                display: inline-flex;
                align-items: center;
                padding: 0.45rem 1rem;
                border-radius: 0.65rem;
                font-size: 0.8125rem;
                font-weight: 600;
                color: #475569;
                background: linear-gradient(165deg, rgba(255, 255, 255, 0.88), rgba(248, 250, 252, 0.78));
                border: 1px solid rgba(255, 255, 255, 0.95);
                box-shadow:
                    inset 2px 2px 5px rgba(163, 177, 198, 0.08),
                    2px 3px 8px rgba(163, 177, 198, 0.06);
                transition:
                    transform 0.15s ease,
                    box-shadow 0.15s ease,
                    color 0.15s ease;
            }

            .admin-prod-filter-tab:hover {
                transform: translateY(-1px);
                color: #232f3e;
            }

            .admin-prod-filter-tab--active {
                color: #fff;
                background: linear-gradient(145deg, #fa8900, #e07800);
                border-color: rgba(255, 255, 255, 0.35);
                box-shadow:
                    4px 6px 14px rgba(250, 137, 0, 0.28),
                    inset 1px 1px 2px rgba(255, 255, 255, 0.35);
            }

            .admin-prod-filter-tab--active:hover {
                color: #fff;
            }

            .admin-prod-filter-dropdown {
                position: relative;
            }

            .admin-prod-filter-tab--menu {
                cursor: pointer;
                gap: 0.35rem;
            }

            .admin-prod-filter-menu {
                position: absolute;
                top: calc(100% + 0.4rem);
                left: 0;
                z-index: 40;
                min-width: 11.5rem;
                padding: 0.35rem;
                border-radius: 0.75rem;
                background: linear-gradient(165deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
                border: 1px solid rgba(255, 255, 255, 0.95);
                box-shadow:
                    8px 12px 28px rgba(163, 177, 198, 0.22),
                    inset 2px 2px 5px rgba(255, 255, 255, 0.9);
            }

            .admin-prod-filter-menu-item {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                width: 100%;
                padding: 0.55rem 0.75rem;
                border-radius: 0.55rem;
                font-size: 0.8125rem;
                font-weight: 600;
                color: #334155;
                transition: background 0.15s ease, color 0.15s ease;
            }

            .admin-prod-filter-menu-item:hover {
                color: #232f3e;
                background: rgba(250, 137, 0, 0.1);
            }

            .admin-prod-filter-menu-item svg {
                width: 1rem;
                height: 1rem;
                flex-shrink: 0;
                color: #fa8900;
            }

            .admin-prod-avatar {
                width: 2.25rem;
                height: 2.25rem;
                border-radius: 9999px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 0.75rem;
                font-weight: 800;
                color: #475569;
                background: linear-gradient(145deg, rgba(248, 250, 252, 0.98), rgba(226, 232, 240, 0.88));
                border: 1px solid rgba(255, 255, 255, 0.95);
                box-shadow:
                    inset 2px 2px 5px rgba(255, 255, 255, 0.9),
                    2px 3px 10px rgba(163, 177, 198, 0.12);
            }

            .admin-prod-role-pill {
                display: inline-flex;
                align-items: center;
                padding: 0.28rem 0.55rem;
                border-radius: 0.5rem;
                font-size: 0.625rem;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                border: 1px solid transparent;
                box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.55);
            }

            .admin-prod-role-pill--admin {
                background: linear-gradient(145deg, rgba(254, 202, 202, 0.92), rgba(255, 255, 255, 0.95));
                color: #991b1b;
                border-color: rgba(239, 68, 68, 0.22);
            }

            .admin-prod-role-pill--dealer {
                background: linear-gradient(145deg, rgba(219, 234, 254, 0.95), rgba(255, 255, 255, 0.98));
                color: #1e40af;
                border-color: rgba(59, 130, 246, 0.22);
            }

            .admin-prod-role-pill--customer {
                background: linear-gradient(145deg, rgba(220, 252, 231, 0.95), rgba(255, 255, 255, 0.98));
                color: #166534;
                border-color: rgba(34, 197, 94, 0.22);
            }

            .admin-prod-role-pill--agent {
                background: linear-gradient(145deg, rgba(243, 232, 255, 0.95), rgba(255, 255, 255, 0.98));
                color: #6b21a8;
                border-color: rgba(147, 51, 234, 0.2);
            }

            .admin-prod-role-pill--teamleader {
                background: linear-gradient(145deg, rgba(254, 249, 195, 0.95), rgba(255, 255, 255, 0.98));
                color: #854d0e;
                border-color: rgba(234, 179, 8, 0.28);
            }

            .admin-prod-role-pill--regional_manager {
                background: linear-gradient(145deg, rgba(224, 242, 254, 0.95), rgba(255, 255, 255, 0.98));
                color: #0c4a6e;
                border-color: rgba(14, 165, 233, 0.25);
            }

            .admin-prod-user-status {
                display: inline-flex;
                padding: 0.28rem 0.65rem;
                border-radius: 9999px;
                font-size: 0.6875rem;
                font-weight: 700;
                border: 1px solid rgba(255, 255, 255, 0.9);
                box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.75);
            }

            .admin-prod-user-status--active {
                background: linear-gradient(145deg, rgba(220, 252, 231, 0.95), rgba(255, 255, 255, 0.92));
                color: #166534;
                border-color: rgba(34, 197, 94, 0.22);
            }

            .admin-prod-user-status--inactive {
                background: linear-gradient(145deg, rgba(241, 245, 249, 0.98), rgba(226, 232, 240, 0.88));
                color: #475569;
                border-color: rgba(148, 163, 184, 0.32);
            }

            .admin-prod-muted {
                color: #94a3b8;
                font-size: 0.8125rem;
            }

            /* Dealers: account status */
            .admin-prod-dealer-status {
                display: inline-flex;
                padding: 0.28rem 0.65rem;
                border-radius: 9999px;
                font-size: 0.6875rem;
                font-weight: 700;
                border: 1px solid rgba(255, 255, 255, 0.9);
                box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.75);
            }

            .admin-prod-dealer-status--active {
                background: linear-gradient(145deg, rgba(220, 252, 231, 0.95), rgba(255, 255, 255, 0.92));
                color: #166534;
                border-color: rgba(34, 197, 94, 0.22);
            }

            .admin-prod-dealer-status--pending {
                background: linear-gradient(145deg, rgba(254, 243, 199, 0.95), rgba(255, 255, 255, 0.92));
                color: #92400e;
                border-color: rgba(245, 158, 11, 0.28);
            }

            .admin-prod-dealer-status--suspended {
                background: linear-gradient(145deg, rgba(254, 226, 226, 0.95), rgba(255, 255, 255, 0.92));
                color: #991b1b;
                border-color: rgba(239, 68, 68, 0.22);
            }

            .admin-prod-link--success {
                color: #059669;
            }

            .admin-prod-link--success:hover {
                color: #047857;
            }

            .admin-prod-btn-inline {
                background: none;
                border: none;
                padding: 0;
                margin: 0;
                cursor: pointer;
                font: inherit;
                font-size: 0.8125rem;
                font-weight: 600;
            }

            /* Detail panels (dealer show, etc.) */
            .admin-prod-detail-body {
                border-top: 1px solid rgba(255, 255, 255, 0.65);
            }

            .admin-prod-detail-row {
                display: grid;
                grid-template-columns: 1fr;
                gap: 0.35rem;
                padding: 1rem 1.25rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.55);
            }

            @media (min-width: 640px) {
                .admin-prod-detail-row {
                    grid-template-columns: 10rem 1fr;
                    gap: 1rem;
                    align-items: start;
                }
            }

            .admin-prod-detail-row:nth-child(odd) {
                background: linear-gradient(90deg, rgba(248, 250, 252, 0.55), rgba(255, 255, 255, 0.15));
            }

            .admin-prod-detail-row:last-child {
                border-bottom: none;
            }

            .admin-prod-detail-dt {
                font-size: 0.8125rem;
                font-weight: 600;
                color: #64748b;
            }

            .admin-prod-detail-dd {
                font-size: 0.875rem;
                color: #0f172a;
                margin: 0;
            }

            .admin-prod-address-card {
                border-radius: 1rem;
                padding: 1.15rem;
                background: linear-gradient(145deg, rgba(255, 255, 255, 0.94), rgba(248, 250, 252, 0.88));
                border: 1px solid rgba(255, 255, 255, 0.92);
                box-shadow:
                    4px 6px 16px rgba(163, 177, 198, 0.12),
                    inset 1px 1px 3px rgba(255, 255, 255, 0.85);
            }

            .admin-prod-tag {
                display: inline-flex;
                padding: 0.2rem 0.5rem;
                border-radius: 0.35rem;
                font-size: 0.65rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                background: rgba(241, 245, 249, 0.95);
                color: #475569;
                border: 1px solid rgba(148, 163, 184, 0.25);
            }

            .admin-prod-tag--accent {
                background: linear-gradient(145deg, rgba(220, 252, 231, 0.9), rgba(255, 255, 255, 0.95));
                color: #166534;
                border-color: rgba(34, 197, 94, 0.22);
            }

            .admin-prod-map-placeholder {
                height: 12rem;
                border-radius: 0.65rem;
                border: 1px solid rgba(255, 255, 255, 0.8);
                background: linear-gradient(165deg, rgba(241, 245, 249, 0.9), rgba(248, 250, 252, 0.7));
                display: flex;
                align-items: center;
                justify-content: center;
                color: #94a3b8;
                font-size: 0.8125rem;
                box-shadow: inset 2px 2px 8px rgba(163, 177, 198, 0.1);
            }

            .admin-prod-map-frame {
                height: 12rem;
                width: 100%;
                border-radius: 0.65rem;
                border: 1px solid rgba(255, 255, 255, 0.8);
                overflow: hidden;
                box-shadow: inset 1px 1px 4px rgba(163, 177, 198, 0.1);
            }

            .admin-prod-map-frame.leaflet-container {
                z-index: 1;
            }

            .admin-prod-actions-bar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.75rem;
                padding: 1.25rem 1.5rem;
                border-radius: 1rem;
                background: linear-gradient(165deg, rgba(255, 255, 255, 0.75), rgba(248, 250, 252, 0.5));
                border: 1px solid rgba(255, 255, 255, 0.85);
                box-shadow:
                    4px 6px 16px rgba(163, 177, 198, 0.1),
                    inset 1px 1px 2px rgba(255, 255, 255, 0.9);
            }

            .admin-prod-btn-primary--success {
                background: linear-gradient(145deg, #10b981, #059669);
                border-color: rgba(255, 255, 255, 0.35);
                box-shadow:
                    4px 6px 16px rgba(16, 185, 129, 0.28),
                    inset 1px 1px 2px rgba(255, 255, 255, 0.35);
            }

            .admin-prod-btn-primary--success:hover {
                box-shadow:
                    6px 8px 20px rgba(16, 185, 129, 0.35),
                    inset 1px 1px 2px rgba(255, 255, 255, 0.4);
            }

            .admin-prod-btn-primary--danger {
                background: linear-gradient(145deg, #ef4444, #dc2626);
                border-color: rgba(255, 255, 255, 0.3);
                box-shadow:
                    4px 6px 16px rgba(239, 68, 68, 0.28),
                    inset 1px 1px 2px rgba(255, 255, 255, 0.25);
            }

            .admin-prod-btn-primary--danger:hover {
                box-shadow:
                    6px 8px 20px rgba(239, 68, 68, 0.35),
                    inset 1px 1px 2px rgba(255, 255, 255, 0.3);
            }

            .admin-prod-form-wide {
                max-width: 48rem;
            }

            /* Primary create/assign forms: clearer card + field chrome */
            .admin-prod-form-shell.admin-clay-panel {
                border: 1px solid rgba(148, 163, 184, 0.62);
                box-shadow:
                    10px 12px 24px rgba(163, 177, 198, 0.28),
                    -6px -8px 20px rgba(255, 255, 255, 0.9),
                    inset 0 0 0 1px rgba(250, 137, 0, 0.14),
                    inset 2px 2px 5px rgba(255, 255, 255, 0.85),
                    inset -2px -3px 8px rgba(148, 163, 184, 0.05);
            }

            .admin-prod-form-shell .admin-prod-form-head {
                border-bottom: 1px solid rgba(148, 163, 184, 0.42);
            }

            /* Select2 alignment with clay forms */
            .admin-prod-select2-wrap .select2-container {
                width: 100% !important;
            }

            .admin-prod-select2-wrap .select2-container--default .select2-selection--single,
            .admin-prod-select2-wrap .select2-container--default .select2-selection--multiple {
                min-height: 2.75rem;
                border-radius: 0.75rem;
                border: 1px solid rgba(148, 163, 184, 0.72);
                background: linear-gradient(165deg, rgba(248, 250, 252, 0.98), rgba(255, 255, 255, 0.99));
                box-shadow:
                    inset 2px 2px 6px rgba(163, 177, 198, 0.08),
                    0 1px 2px rgba(15, 23, 42, 0.04);
                padding: 0.2rem 0.35rem;
            }

            .admin-prod-select2-wrap .select2-container--default.select2-container--focus .select2-selection--single,
            .admin-prod-select2-wrap .select2-container--default.select2-container--focus .select2-selection--multiple {
                border-color: rgba(250, 137, 0, 0.85);
                box-shadow: 0 0 0 3px rgba(250, 137, 0, 0.2);
            }

            .admin-prod-select2-wrap .select2-container--default .select2-selection--multiple .select2-selection__choice {
                background: linear-gradient(145deg, rgba(255, 247, 237, 0.95), rgba(255, 255, 255, 0.98));
                border: 1px solid rgba(251, 146, 60, 0.35);
                border-radius: 0.4rem;
            }
        </style>
    @endpush
@endonce
