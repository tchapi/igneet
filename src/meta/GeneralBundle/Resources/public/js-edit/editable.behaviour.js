$(document).ready(function(){
  
  /* editable elements */
  $('.editable').editable({
    pk: 1,
    placement: 'bottom'
  });
  
  /* editable list of ul/li */
  $('.editable-li').attr('data-value', $.map( $('.editable-li li'), function (element) { return $(element).attr('rel') }).join(',') );
  $('.editable-li').editable({
    pk: 1,
    placement: 'bottom',
    display:   function(value, sourceData) {
                    $(this).empty();
                    var selected = $.grep(sourceData,function(e,i){
                      return (value.indexOf(e.value) != -1);
                    });
                    for(item in selected){
                      $(this).append('<li class="label" rel="' + selected[item].value + '">' + selected[item].text + '</li>');
                    }
                }
  });


  /* User profile specific */
  $('.editable-trigger').click(function(e) {
    e.stopPropagation();
    if (e.target.tagName == 'I') // icon ...
      target = e.target.parentNode.getAttribute('data-target');
    else // a or span
      target = e.target.getAttribute('data-target');
    $('.' + target + '-target').editable('toggle');
  });

});