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
      $.post(list.attr('data-url'), {
        ranks: ranks
      })
      .error(function(errors) {
        setFlash('error', 'There was an error saving changes.');
      });

      // Update parenting
      $.post(item.attr('data-url'), {
        name: item.attr('data-name'),
        value: item.parent().attr('data-value')
      })
      .error(function(errors) {
        setFlash('error', 'There was an error saving changes.');
      });

  };

  // Add expand / collapse behaviour
  /*
  <menu id="nestable-menu">
      <button type="button" data-action="expand-all">Expand All</button>
      <button type="button" data-action="collapse-all">Collapse All</button>
  </menu>
  $('#nestable-menu').on('click', function(e) {
      var target = $(e.target),
          action = target.data('action');
      if (action === 'expand-all') {
          $('.dd').nestable('expandAll');
      }
      if (action === 'collapse-all') {
          $('.dd').nestable('collapseAll');
      }
  });
  */

  // Triggers nestable()
  sortableList.nestable({
    listNodeName: 'ul',
    callback : updateRanksAndNesting
  });

});