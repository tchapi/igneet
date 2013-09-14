var setFlash = function(type, message){

  var uniqid = "flash_" + Date.now();

  $('<div/>',{
      id: uniqid,
      html: message + '<button type="button" class="close" data-dismiss="alert">×</button>'
  }).addClass('alert alert-' + type).appendTo('#flashes');

  window.setTimeout(function(){ $('#' + uniqid).fadeOut(); }, 2000);

};

$(document).ready(function(){

  // Focus the first field of the first form found on the page
  // (Useful for login / invite /etc )
  $('form').find('input').first().focus();

  // Count notifications
  $.post($("#notificationsCount").attr('data-update-path'), function(data) {

    $("#notificationsCount").html(data);

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