(function($) {
  "use strict";
  
  // Bootstrap-modal
  $.fn.modal.defaults.spinner = $.fn.modalmanager.defaults.spinner = 
  '<div class="loading-spinner" style="width: 200px; margin-left: -100px;">' +
    '<div class="progress progress-striped active">' +
      '<div class="progress-bar" style="width: 100%;"></div>' +
    '</div>' +
  '</div>';

  // Bootstrap-modal plugin ajax example
  var $modal = $('#ajax');
  $('.btn[data-target="#ajax"]').on('click', function(){
    // create the backdrop and wait for next modal to be triggered
    $('body').modalmanager('loading');
   
    setTimeout(function(){
       $modal.load('../../dist/material/plugins/bootstrap-modal/modal_ajax_test.html', '', function(){
        $modal.modal();
      });
    }, 1000);
  });
   
  $modal.on('click', '.update', function(){
    $modal.modal('loading');
    setTimeout(function(){
      $modal
        .modal('loading')
        .find('.modal-body')
          .prepend('<div class="alert alert-info fade in">' +
            'Updated!<button type="button" class="close" data-dismiss="alert">&times;</button>' +
          '</div>');
    }, 1000);
  });
})(jQuery);