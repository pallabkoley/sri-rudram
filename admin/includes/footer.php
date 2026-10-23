  </div><!-- /.content-area -->
</div><!-- /.main-content -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables core + Bootstrap 5 + Responsive -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
/* ============================================================
   Global DataTables defaults — applied to every table on the
   admin panel that uses class="admin-datatable"
   ============================================================ */
if (window.jQuery && $.fn.dataTable) {
  $.extend(true, $.fn.dataTable.defaults, {
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
    order: [],
    responsive: true,
    autoWidth: false,
    language: {
      search: '',
      searchPlaceholder: 'Type to search…',
      lengthMenu: 'Show _MENU_ entries',
      info: 'Showing _START_ to _END_ of _TOTAL_ entries',
      infoEmpty: 'No entries',
      infoFiltered: '(filtered from _MAX_ total)',
      zeroRecords: 'No matching records found',
      emptyTable: 'No data available',
      paginate: {
        first:    '<i class="bi bi-chevron-double-left"></i>',
        previous: '<i class="bi bi-chevron-left"></i>',
        next:     '<i class="bi bi-chevron-right"></i>',
        last:     '<i class="bi bi-chevron-double-right"></i>'
      }
    },
    dom:
      "<'dt-top'<'dt-length'l><'dt-search'f>>" +
      "<'dt-table'tr>" +
      "<'dt-bottom'<'dt-info'i><'dt-pages'p>>"
  });

  /* Auto-init any table with class="admin-datatable"
     Optional data attributes on the <table>:
       data-order="7,asc"   -> initial sort column,index
       data-page-length="25"
       data-no-sort="0,9"   -> comma-separated column indexes that should not be sortable
  ------------------------------------------------ */
  $(function () {
    $('table.admin-datatable').each(function () {
      var $t = $(this);
      if ($.fn.DataTable.isDataTable(this)) return;

      var orderAttr = $t.data('order');
      var order = [];
      if (orderAttr) {
        var parts = String(orderAttr).split(',');
        order = [[parseInt(parts[0], 10), (parts[1] || 'asc')]];
      }

      var noSort = $t.data('no-sort');
      var colDefs = [];
      if (noSort !== undefined && noSort !== '') {
        var idxs = String(noSort).split(',').map(function (n) { return parseInt(n, 10); });
        colDefs.push({ targets: idxs, orderable: false });
      }

      $t.DataTable({
        order: order,
        pageLength: $t.data('page-length') || 10,
        columnDefs: colDefs
      });
    });
  });
}
</script>

<!-- Admin JS -->
<script src="<?= asset('admin/assets/js/admin.js') ?>" defer></script>

</body>
</html>