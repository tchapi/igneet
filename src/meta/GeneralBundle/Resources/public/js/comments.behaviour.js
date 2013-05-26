$(document).ready(function(){
  
  // Toggle icon for comments
  $('.commentsBoxToggle').click(function(){

    $(this).parent().find('.commentsBoxTitle').fadeToggle();
    $(this).parent().find('.commentsContent').slideToggle();

  });

  // Toggle the height of the commenting input
  $('.commentsForm textarea').blur(function(){
    if ( $(this).val() == "" ) $(this).animate({height:'20px'});
  });
  $('.commentsForm textarea').focus(function(){
    $(this).animate({height:'60px'});
  });

  // Tooltips
  $('.tooltip-trigger').tooltip({html: true});

  // Validates in AJAX
  $('.validate-trigger').click(function(){

    var countBox = $(this).siblings('span');
    var validationBox = $(this).closest('.validations');

    $.post($(this).attr('data-url'))
      .success(function(data, config) {
        countBox.html(data);
        validationBox.toggleClass('validated');
      })
      .error(function(errors) {
        setFlash('error', Translator.get('comment.cannot.validate'));
      });

  });

  // Deletes in AJAX
  $('.delete-trigger').click(function(){

    var actionBox = $(this).closest('.actions');
    var commentBox = actionBox.siblings('.comment').find('div');

    $.post($(this).attr('data-url'))
      .success(function(data, config) {
        commentBox.html('<p class="muted"><em>' + Translator.get('comment.deleted') + '</em></p>');
        actionBox.fadeOut();
        setFlash('success', Translator.get('comment.been.deleted'));
      })
      .error(function(errors) {
        setFlash('error', Translator.get('comment.cannot.delete'));
      });

  });

});