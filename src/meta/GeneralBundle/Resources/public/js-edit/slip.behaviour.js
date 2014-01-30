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

        // Compute ranks
        ul = $(e.target.parentNode);
        console.log(ul);
        var ranks = ul.find('li').map(function() {
                      return this.id;
                    }).get().join();

        // Update rank
        $.post(ul.attr('data-url'), {
          ranks: ranks
        })
        .success(function() {
          alertify.success(Translator.trans('alert.changes.saved'));
        })
        .error(function(errors) {
          alertify.error(Translator.trans('alert.error.saving.changes'));
        });
      }

  });

  list.addEventListener('slip:beforewait', function(e){
    if (e.target.parentNode.className.indexOf('instant') > -1) e.preventDefault();
  }, false); 

  list.addEventListener('slip:afterswipe', function(e) {
      // e.target list item swiped
      e.target.parentNode.removeChild(e.target);
      $.post($(e.target).attr('data-delete'));
  });

  // new item
  $("ul.slip > li > input")
    .on("keyup", function(e) {
      if (e.which == '13'){ // Trigger a save with the Return key for new item
        e.preventDefault();
        parent = $(this).closest('ul');
        text = $(this).val();
        url = $(this).parent().attr("data-url");
        _this = $(this);
        $.post(url, {text: text}, function(data){
          parent.children().last().before(data);
          _this.val('');
        });
      }
    });

    // toggle item
    $(document).on('click', "ul.slip > li > .actions > a", function(){
      li = $(this).closest('li');
      url = $(this).attr("data-url");
      $.post(url, function(data){
        li.replaceWith(data);
        val = $("ul.slip > li.done").length / ($("ul.slip > li").length - 1 ) * 100;
        console.log(val);
        $('.label-progress > span').width(val + "%");
      });
    });

});