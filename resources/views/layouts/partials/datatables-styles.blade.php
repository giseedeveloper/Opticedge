@once('optic-datatables-styles')
    @push('styles')
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
        <style>
            .dataTables_wrapper .dataTables_filter input,
            .dataTables_wrapper .dataTables_length select {
                border: 1px solid rgba(148, 163, 184, 0.6);
                border-radius: 0.5rem;
                padding: 0.35rem 0.5rem;
                background: #fff;
            }

            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                margin-top: 0.75rem;
                font-size: 0.8125rem;
                color: #64748b;
            }

            .admin-prod-table-wrap .dataTables_wrapper,
            .admin-clay-panel .dataTables_wrapper {
                padding: 0.75rem 1rem 1rem;
            }
        </style>
    @endpush
@endonce
