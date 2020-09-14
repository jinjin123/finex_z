(function($) {
  "use strict";
  
  $(function(){
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
      },
      buttons: [
        {
          extend: 'copy',
          className: 'btn-default-outline'
        },
        {
          extend: 'excel',
          className: 'btn-default-outline'
        },
        {
          extend: 'csv',
          className: 'btn-default-outline'
        },
        {
          extend: 'pdf',
          className: 'btn-default-outline'
        },
        {
          extend: 'print',
          className: 'btn-default-outline',
          customize: function( win ){
            $(win.document.body).css({
              'padding-left': '20px',
              'padding-right': '20px',
              'background-color': 'transparent'
            });
          }
        }
      ]
    });

    // Buttons example
    var dtButtons = $('#datatable-buttons');
    dtButtons.DataTable({
      dom: '<"row" <"col-sm-6" l> <"col-sm-6" <"pull-right"B> f> >'+
           '<"row" <"col-sm-12" t> >'+
           '<"row m-top-10" <"col-sm-6" i> <"col-sm-6" p> >'
    });

    // Moving search to .panel-heading
    dtButtons = $('#datatable-buttons_filter input');
    dtButtons.appendTo(dtButtons.closest('.panel').find('.panel-heading .tools-content'));
    dtButtons.closest('#datatable-buttons_filter').remove();

    // Buttons example
    var dtDropdown = $('#datatable-dropdown');
    dtDropdown.DataTable({
      dom: '<"row" <"col-sm-6" <"inline-block m-right-15" l><"inline-block" B> > <"col-sm-6" f> >'+
           '<"row" <"col-sm-12" t> >'+
           '<"row m-top-10" <"col-sm-6" i> <"col-sm-6" p> >',
      buttons: [{
        extend: 'collection',
        text: 'Export',
        className: 'btn-default-outline',
        buttons: ['copy', 'excel', 'csv', 'pdf', 'print']
      }]
    });

    // Control table example
    $('#datatable-control').DataTable({
      dom: '<"row" <"col-sm-6" <"inline-block m-right-15" l><"inline-block" B>> <"col-sm-6" f> >'+
           '<"row" <"col-sm-12" t> >'+
           '<"row m-top-10" <"col-sm-6" i> <"col-sm-6" p> >',
      buttons: [{
        extend: 'collection',
        text: 'Swtich on/off',
        className: 'btn-default-outline',
        buttons: [
          {
            text: 'Salary',
            action: function(e, dt, node, config){
              dt.column(-1).visible(! dt.column(-1).visible());
            }
          },
          {
            text: 'Date',
            action: function(e, dt, node, config){
              dt.column(-2).visible(! dt.column(-2).visible());
            }
          },
          {
            text: 'Age',
            action: function(e, dt, node, config){
              dt.column(-3).visible(! dt.column(-3).visible());
            }
          },
          {
            text: 'Office',
            action: function(e, dt, node, config){
              dt.column(-4).visible(! dt.column(-4).visible());
            }
          },
          {
            text: 'Position',
            action: function(e, dt, node, config){
              dt.column(-5).visible(! dt.column(-5).visible());
            }
          },
          {
            text: 'Name',
            action: function(e, dt, node, config){
              dt.column(-6).visible(! dt.column(-6).visible());
            }
          }
        ]
      }]
    });

    // Responsive example
    $('.datatable-responsive').DataTable({
      responsive: true,   //是否自适应
      bPaginate: false,  //是否分页功能
      bInfo:false,  //是否表格信息
      bLengthChange: false,  //是否显示记录数
      bFilter: false,  //是否显示分页
    });

    $('#datatable-fixed-header').DataTable({
      fixedHeader: {
        headerOffset: 50
      }
    });

    $('#datatable-select').DataTable({
      select: true
    });

    // Select2
    $('select').select2({
      theme: 'bootstrap'
    });
  });
})(jQuery);