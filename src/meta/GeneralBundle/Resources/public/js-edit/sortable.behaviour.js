$(document).ready(function(){

  var oldContainer;
  var oldIndex;

  $("ul.sortable").sortable({
    group: 'nested', // if <ul> in <li>
    afterMove: function (placeholder, container) {
      if(oldContainer != container){
        if(oldContainer)
          oldContainer.el.removeClass("highlight")
        container.el.addClass("highlight")
        
        oldContainer = container
      }
    },
    onDragStart: function (item, group, _super) {
      oldIndex = item.index()
      _super(item)
    },
    onDrop: function (item, container, _super) {

      var root = item.closest('ul.sortable');
      // Compute ranks
      var ranks = "";
      root.find('li').each(function()
      {
        ranks += $(this).attr('id')+",";
      });

      // Update rank
      $.post(root.attr('data-url'), {
        ranks: ranks
      })
      .success(function(data, config) {
         //console.log("Ranks saved.");       
      })
      .error(function(errors) {
         //console.log("Error saving ranks.");
      });


      // Update parenting
      $.post(item.attr('data-url'), {
        name: item.attr('data-name'),
        value: container.el.attr('data-value')
      })
      .success(function(data, config) {
         //console.log("Parenting changes saved.");       
      })
      .error(function(errors) {
         //console.log("Error saving parenting changes.");
      });

      container.el.removeClass("highlight")
      _super(item);
    }
  });

});