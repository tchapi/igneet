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

});