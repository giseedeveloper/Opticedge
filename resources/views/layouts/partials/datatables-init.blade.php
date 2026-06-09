@once('optic-datatables-init')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.jQuery || !jQuery.fn.DataTable) {
                return;
            }

            const selector = '.admin-prod-table-wrap table, .admin-clay-panel > table, table.js-datatable';

            document.querySelectorAll(selector).forEach(function (table) {
                if (shouldSkipTable(table)) {
                    return;
                }

                if (jQuery.fn.dataTable.isDataTable(table)) {
                    return;
                }

                jQuery(table).DataTable(buildOptions(table));
            });

            function shouldSkipTable(table) {
                if (table.hasAttribute('data-no-datatable')) {
                    return true;
                }

                if (table.closest('form')) {
                    return true;
                }

                if (table.closest('.admin-prod-form-shell, #dist-products-tabs-wrap, .admin-report-table-container')) {
                    return true;
                }

                return false;
            }

            function buildColumnDefs(table) {
                const defs = [];
                const headers = table.querySelectorAll('thead th');

                headers.forEach(function (th, index) {
                    const label = th.textContent.trim().toLowerCase();
                    const isAction = label === 'action' || label === 'actions';
                    const isImage = th.classList.contains('admin-prod-th--image') || label === 'image';

                    if (isAction || isImage) {
                        defs.push({
                            targets: index,
                            orderable: false,
                            searchable: false,
                        });
                    }
                });

                return defs;
            }

            function buildOptions(table) {
                const columnDefs = buildColumnDefs(table);
                const options = {
                    pageLength: 25,
                    lengthMenu: [10, 25, 50, 100, 250],
                    order: [],
                    autoWidth: false,
                    language: {
                        search: 'Search:',
                        lengthMenu: 'Show _MENU_ entries',
                        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                        infoEmpty: 'No entries found',
                        emptyTable: 'No data available.',
                        zeroRecords: 'No matching entries found',
                    },
                };

                if (columnDefs.length) {
                    options.columnDefs = columnDefs;
                }

                if (table.dataset.datatableOrder) {
                    const parts = table.dataset.datatableOrder.split(',');
                    if (parts.length === 2) {
                        options.order = [[parseInt(parts[0], 10), parts[1].trim()]];
                    }
                }

                if (table.dataset.datatablePageLength) {
                    options.pageLength = parseInt(table.dataset.datatablePageLength, 10);
                }

                if (table.dataset.datatableOrdering === 'false') {
                    options.ordering = false;
                }

                return options;
            }
        });
    </script>
@endonce
