var setFlash = function(type, message){

  var uniqid = "flash_" + Date.now();

  $('<div/>',{
      id: uniqid,
      html: message + '<button type="button" class="close" data-dismiss="alert">×</button>'
  }).addClass('alert alert-' + type).appendTo('#flashes');

  window.setTimeout(function(){ $('#' + uniqid).fadeOut(); }, 2000);

};