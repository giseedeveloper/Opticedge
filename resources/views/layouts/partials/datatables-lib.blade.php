@once('optic-datatables-lib')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script>
        if (window.jQuery && jQuery.fn.dataTable) {
            jQuery.fn.dataTable.ext.errMode = 'none';
        }
    </script>
@endonce
