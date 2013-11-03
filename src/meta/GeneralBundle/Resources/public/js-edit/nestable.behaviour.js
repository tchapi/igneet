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
        setFlash('error', Translator.get('alert.error.saving.changes'));
      });

      // Update parenting
      $.post(item.attr('data-url'), {
        name: item.attr('data-name'),
        value: item.parent().parent().attr('id') || 0
      })
      .error(function(errors) {
        setFlash('error', Translator.get('alert.error.saving.changes'));
      });

  };

  // Triggers nestable()
  sortableList.nestable({
    listNodeName: 'ul',
    expandBtnHTML: '<button data-action="expand"><i class="fa fa-folder-o"></i></button>',
    collapseBtnHTML: '<button data-action="collapse"><i class="fa fa-folder-open-o"></i></button>',
    callback : updateRanksAndNesting
  });

});