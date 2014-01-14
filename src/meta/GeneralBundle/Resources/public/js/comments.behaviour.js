$(document).ready(function(){
  
  // Validates in AJAX
  $('.validate-trigger').click(function(){

    var countBox = $(this).siblings('span');
    var validationBox = $(this).closest('.validation');

    $.post($(this).attr('data-url'))
      .success(function(data, config) {
        countBox.html(data);
        validationBox.toggleClass('validated');
        alertify.success(Translator.trans('comment.validated'));
      })
      .error(function(errors) {
        alertify.error(Translator.trans('comment.cannot.validate'));
      });

  });

  // Deletes in AJAX
  $('.delete-trigger').click(function(){

    var actionBox = $(this).closest('.actions');
    var commentBox = actionBox.siblings('.text');

    $.post($(this).attr('data-url'))
      .success(function(data, config) {
        commentBox.html('<em>' + Translator.trans('comment.deleted') + '</em>');
        actionBox.fadeOut();
        alertify.success(Translator.trans('comment.been.deleted'));
      })
      .error(function(errors) {
        alertify.error(Translator.trans('comment.cannot.delete'));
      });

  });

});