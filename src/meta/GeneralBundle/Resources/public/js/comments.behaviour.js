$(document).ready(function(){
  
  // Toggle icon for comments
  $('.commentsBoxToggle').click(function(){

    $(this).parent().find('.commentsBoxTitle').fadeToggle();
    $(this).parent().find('.commentsContent').slideToggle();

  });

});