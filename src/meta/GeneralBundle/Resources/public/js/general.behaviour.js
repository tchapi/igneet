$(document).ready(function(){
  
});

var setFlash = function(type, message){

  $('<div/>',{
      class: 'alert alert-' + type,
      html: message + '<button type="button" class="close" data-dismiss="alert">×</button>'
  }).appendTo('#flashes');

};