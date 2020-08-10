(function($) {
  "use strict";
  
  $(function(){
    // Icheck
    $('input.icheck').iCheck({
      checkboxClass: 'icheckbox_minimal-grey',
      radioClass: 'iradio_minimal-grey'
    });

    // Datatables
    //Setting default values
    $.extend( $.fn.dataTable.defaults,{ 
      dom:  '<"row" <"col-sm-6" l> <"col-sm-6" f> >'+ // Top section structure(search etc.)
            '<"row" <"col-sm-12" t> >'+                       // Table
            '<"row m-top-10" <"col-sm-6" i> <"col-sm-6" p> >',         // Bottom section structure (pagination etc.)
      autoWidth: false,
      order: [ [0, 'desc'] ],
      pagingType: 'full_numbers',
      language: {
        lengthMenu: '_MENU_',
        info: 'Showing page _PAGE_ of _PAGES_',
        infoEmpty: 'No records available',
        infoFiltered: '(From _MAX_ total records)',
        search: '_INPUT_',
        searchPlaceholder: 'Search...',
        paginate: {
          next:       '<i class="fa fa-angle-right"></i>',
          previous:   '<i class="fa fa-angle-left"></i>'
        },
      } 
    });

    // Basic example
    $('#datatable-basic').DataTable();

    // Checkbox example
    var dtCheckbox = $('#datatable-checkbox');
    dtCheckbox.DataTable({
      order: [ [1, 'desc'] ],
      columnDefs:[{
        targets: 0,
        orderable: false
      }],
      initComplete: function(settings, json){
        $('.icheck').on('ifChecked', function(event){
          var thCheckbox = $(this).closest('th');
          var tdCheckbox = $(this).closest('.table').find('tbody input.icheck');

          if(thCheckbox.length)
            tdCheckbox.iCheck('check');
        });

        $('.icheck').on('ifUnchecked', function(event){
          var thCheckbox = $(this).closest('th');
          var tdCheckbox = $(this).closest('.table').find('tbody input.icheck');

          if(thCheckbox.length)
            tdCheckbox.iCheck('uncheck');
        });
      }
    });

    // Vertical scroll example
    $('#datatable-vertical').DataTable({
      scrollY: '200px',
      scrollCollapse: true
    });

    // Horizontal scroll example
    $('#datatable-horizontal').DataTable({
      scrollX: true
    });

    // Row group example
    var dtRowGroup = $('#datatable-row-group');
    dtRowGroup.DataTable({
      columnDefs: [
        {
          'visible': false,
          'targets': 2
        }
      ],
      order: [ [2, 'asc'] ],
      displayLength: 25,
      drawCallback: function(settings){
        var api = this.api();
        var rows = api.rows({page:'current'}).nodes();
        var last=null;

        api.column(2, {page:'current'}).data().each(function(group, i){
          if(last !== group) {
            $(rows).eq( i ).before(
              '<tr class="group"><td colspan="5">'+group+'</td></tr>'
            );

            last = group;
          }
        });
      }
    });

    // Order by the grouping
    dtRowGroup.find('tbody').on( 'click', 'tr.group', function(){
      var currentOrder = dtRowGroup.DataTable().order()[0];
      if(currentOrder[0] === 2 && currentOrder[1] === 'asc'){
        dtRowGroup.DataTable().order([2, 'desc']).draw();
      }else{
        dtRowGroup.DataTable().order([2, 'asc' ]).draw();
      }
    });

    // Select2
    $('select').select2({
      theme: 'bootstrap'
    });
  });
})(jQuery);