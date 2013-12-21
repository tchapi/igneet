var setFlash = function(type, message){

  var uniqid = "flash_" + Date.now();

  $('<div/>',{
      id: uniqid,
      html: message + '<button type="button" class="close" data-dismiss="alert">×</button>'
  }).addClass('alert alert-' + type).appendTo('#flashes');

  window.setTimeout(function(){ $('#' + uniqid).fadeOut(); }, 2000);

};

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

  // Focus the first field of the first form found on the page
  // (Useful for login / invite /etc )
  //$('form').find('input').first().focus();

  // Count notifications
  $.post($("#notificationsCount").attr('data-update-path'), function(data) {

    $("i[role=loading]").remove();
    $("i[role=loaded]").show();
    $(".notificationsCount").html(data).show();

  });

  // Select all of shortcode when visible
  $("#shortcode-trigger").click(function(){
    $("#shortcode").toggle(200, function(){

      if ($("#shortcode").is(":visible")){
        $("#shortcode input").focus();
        $("#shortcode input:text").select();
      } else {
        $("#shortcode input").blur();
      }

    });
  });

});