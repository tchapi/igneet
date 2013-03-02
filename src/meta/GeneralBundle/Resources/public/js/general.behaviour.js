var setFlash = function(type, message){

  $('<div/>',{
      html: message + '<button type="button" class="close" data-dismiss="alert">×</button>'
  }).addClass('alert alert-' + type).appendTo('#flashes');

};