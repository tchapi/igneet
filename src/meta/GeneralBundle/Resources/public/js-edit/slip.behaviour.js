$(document).ready(function(){

  var list = document.querySelector('ul.slip');
  new Slip(list);

  list.addEventListener('slip:reorder', function(e) {
      // e.target list item reordered.
      // e.detail.insertBefore == null means we're at the end of the list, below the "new"
      if ($(e.target).hasClass('new') || e.detail.insertBefore == null ) {
        e.preventDefault();
      } else {
        e.target.parentNode.insertBefore(e.target, e.detail.insertBefore);
      }
      // post rank
  });

  list.addEventListener('slip:beforewait', function(e){
    if (e.target.parentNode.className.indexOf('instant') > -1) e.preventDefault();
  }, false); 

  list.addEventListener('slip:afterswipe', function(e) {
      // e.target list item swiped
      e.target.parentNode.removeChild(e.target);
      $.post($(e.target).attr('data-delete'));
  });

});