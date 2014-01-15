$(document).ready(function(){

  var sortableList = $(".dd");

  // Callback for ranks and nesting
  var updateRanksAndNesting = function(l, e) {
    
      var list = l.length ? l : $(l.target),
          item = e.length ? e : $(e.target);

      // Compute ranks
      var ranks = list.find('li').map(function() {
                    return this.id;
                  }).get().join();

      // Update rank
      $.post(list.children('ul').attr('data-url'), {
        ranks: ranks
      })
      .error(function(errors) {
        alertify.error(Translator.trans('alert.error.saving.changes'));
      });

      // Update parenting
      $.post(item.attr('data-url'), {
        name: item.attr('data-name'),
        value: item.parent().parent().attr('id') || 0
      })
      .error(function(errors) {
        alertify.error(Translator.trans('alert.error.saving.changes'));
      });

  };

  // Triggers nestable()
  sortableList.nestable({
    listNodeName: 'ul',
    expandBtnHTML: '<a data-action="expand"><i class="fa fa-caret-right"></i></a>',
    collapseBtnHTML: '<a data-action="collapse"><i class="fa fa-caret-down"></i></a>',
    callback : updateRanksAndNesting
  });

  // Toggle view
  $(".tree > .toggle").click(function(e){
    $(this).parent().toggleClass("open");
    e.preventDefault();
  });
});