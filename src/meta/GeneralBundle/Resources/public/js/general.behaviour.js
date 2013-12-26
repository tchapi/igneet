$(document).ready(function(){

  /*
    Responsive slide menu
  */
  var $menu_trigger = $(".menu-trigger");
  if ( typeof $menu_trigger !== 'undefined' ) {
    $menu_trigger.on('click', function() {
        if ($("body").hasClass('menu-active') ){
          $("body").removeClass('menu-active');
        } else {
          $("body").addClass('menu-active');
        }
    });
  }

  /* 
    Sub menu / Dropdowns 
  */
  $("li.dropdown > a").on('click', function(e){
    if (!($("nav[role=mobile]").is(':visible')))
      $(this).parent().toggleClass("active").find("ul").toggle().focus();
    else
      e.preventDefault();
  });

  $("li.dropdown > ul").focusout(function(){
    if (!($("nav[role=mobile]").is(':visible')))
      setTimeout(function () {
        if ($(document.activeElement).parents('.active').length === 0) {
          $("li.dropdown").removeClass("active").find("ul").hide();
        }
      }, 1);
  });

  // Choose a user - select box
  $("select#communityUsername").change(function(){
    if ($(this).val() == -1 ){
      $("input#mailOrUsername").prop('disabled', false);
    } else {
      $("input#mailOrUsername").prop('disabled', true);
    }
    
  });

  // Count notifications
  $.post($("#notificationsCount").attr('data-update-path'), function(data) {

    $("i[role=loading]").remove();
    $("i[role=loaded]").show();
    $(".notificationsCount").html(data).show();

  });

  // Select all of shortcode when visible
  $("#shortcode-trigger").click(function(){
    $("#shortcode").fadeToggle(200, function(){

      if ($("#shortcode").is(":visible")){
        $("#shortcode input").focus();
        $("#shortcode input:text").select();
      } else {
        $("#shortcode input").blur();
      }

    });
  });

});