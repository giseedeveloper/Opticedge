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

                if (!hasConsistentColumns(table)) {
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

            function countRowColumns(row) {
                let count = 0;

                row.querySelectorAll(':scope > th, :scope > td').forEach(function (cell) {
                    count += parseInt(cell.getAttribute('colspan') || '1', 10);
                });

                return count;
            }

            function getHeaderColumnCount(table) {
                const headerRows = table.querySelectorAll('thead tr');

                if (!headerRows.length) {
                    return 0;
                }

                return countRowColumns(headerRows[headerRows.length - 1]);
            }

            function hasConsistentColumns(table) {
                const expected = getHeaderColumnCount(table);

                if (expected === 0) {
                    return false;
                }

                const bodyRows = table.querySelectorAll('tbody tr');

                for (let i = 0; i < bodyRows.length; i++) {
                    const row = bodyRows[i];

                    if (row.querySelector('table')) {
                        return false;
                    }

                    const cells = row.querySelectorAll(':scope > td, :scope > th');

                    if (cells.length === 1 && cells[0].hasAttribute('colspan')) {
                        continue;
                    }

                    if (countRowColumns(row) !== expected) {
                        return false;
                    }
                }

                return true;
            }

            function buildColumnDefs(table) {
                const defs = [];
                const headerRows = table.querySelectorAll('thead tr');
                const headerRow = headerRows.length
                    ? headerRows[headerRows.length - 1]
                    : null;

                if (!headerRow) {
                    return defs;
                }

                headerRow.querySelectorAll('th, td').forEach(function (th, index) {
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
