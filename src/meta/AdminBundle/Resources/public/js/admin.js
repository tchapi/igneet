$(document).ready(function(){
  
  $(".storage").on('click', function(e){
    e.preventDefault();

    $.post($(this).attr('href')).done(function(data){
      alertify.alert(data.total);
    });

  });

  if ($("#total_storage").length == 1) {
    $.post($("#total_storage").attr('data-url')).done(function(data){
        $("#total_storage").html(data.total);
        $("#total_files").html(data.count);
      });
  }

});
