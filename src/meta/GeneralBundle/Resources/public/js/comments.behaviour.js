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
        setFlash('error', 'There was a problem validating this comment.');
      });

  });

  // Deletes in AJAX
  $('.delete-trigger').click(function(){

    var commentBox = $(this).closest('.actions').siblings('.comment').find('div');

    $.post($(this).attr('data-url'))
      .success(function(data, config) {
        commentBox.html('<p class="muted"><em>Deleted comment</em></p>');
        setFlash('success', 'Your comment was deleted.');
      })
      .error(function(errors) {
        setFlash('error', 'There was a problem with the deletion of this comment.');
      });

  });

});