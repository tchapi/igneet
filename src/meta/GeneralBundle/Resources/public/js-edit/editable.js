$(document).ready(function(){
  
  $('.editable').editable({
    pk: 1,
    placement: 'bottom'
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