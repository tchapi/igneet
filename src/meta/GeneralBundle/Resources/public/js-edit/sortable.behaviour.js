$(document).ready(function(){

  var oldContainer;
  $("ul.sortable").sortable({
    group: 'nested',
    afterMove: function (placeholder, container) {
      if(oldContainer != container){
        if(oldContainer)
          oldContainer.el.removeClass("highlight")
        container.el.addClass("highlight")
        
        oldContainer = container
      }
    },
    onDrop: function (item, container, _super) {

      $.post(item.attr('data-url'), {
        name: item.attr('data-name'),
        value: container.el.attr('data-value')
      })
      .success(function(data, config) {
         //console.log("Changes saved.");       
      })
      .error(function(errors) {
         //console.log("Error saving changes.");
      });

      container.el.removeClass("highlight")
      _super(item)
    }
  });

});