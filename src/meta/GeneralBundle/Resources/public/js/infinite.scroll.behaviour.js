$.fn.isOnScreen = function(){
   
  var win = $(window);
   
  var viewport = {
      top : win.scrollTop(),
      left : win.scrollLeft()
  };
  viewport.right = viewport.left + win.width();
  viewport.bottom = viewport.top + win.height();
   
  var bounds = this.offset();
  bounds.right = bounds.left + this.outerWidth();
  bounds.bottom = bounds.top + this.outerHeight();
   
  return (!(viewport.right < bounds.left || viewport.left > bounds.right || viewport.bottom < bounds.top || viewport.top > bounds.bottom));
   
};

$(document).ready(function(){

  $('#loading').hide();
  $('#no-more').hide();

  page = 1;

  canLoad = true; // Infinite scrolling is enabled
  
  var template = $('#list tr.template').html(); // We get the template for the infinite scrolling

  // Handle urls like this : /[objects]#page=N
  gotoPage = location.hash.match(new RegExp('page=([^&]*)'))
  gotoPage = gotoPage?gotoPage[1]:1;

  // If we have a hash that tells us to go to a certain page, we need to request the missing elements
  if (gotoPage !== 1) {

    canLoad = false; // While we're doing this, prevent other calls
    $('#loading').show();
    $('#more').hide();

    // Load intermediary pages
    $.ajax({
      type: "POST",
      url: url,
      data: {page: gotoPage, full: true},
      success: function(data) {
        
        for(key in data) {
          str = template.replace(/%\w+%/g, function(all) {
            return data[key][all.replace(/%/g, '')] || "";
          });
          str = str.replace(/data\-/g, '')
          $('#list tr:last').after('<tr href="page' + page + '">' + str + '</tr>');
        }
        
        $('#loading').hide();

        if ((page-1)* objects_per_page > total_objects) {
          $('#more').hide();
          $('#no-more').show();
          canLoad = false;
        } else {
          $('#more').show();
          $('#no-more').hide();
          canLoad = true;
        }
      }
    });

    // Finally, update the page parameter for the next calls
    page = gotoPage;
  }

  // When we scroll
  $( window ).scroll(function() {

    if (canLoad && $("#more").isOnScreen()) {

      $('#loading').show();
      $('#more').hide();
      $('#no-more').hide();

      page++;

      if ((page-1)* objects_per_page > total_objects) {

        $('#more').hide();
        $('#loading').hide();
        $('#no-more').show();
        canLoad = false; // We're at the end of the results array, anyway..
      
      } else {
      
        canLoad = false; // While we're doing this, prevent other calls
        $.ajax({
          type: "POST",
          url: url,
          data: {page: page},
          success: function(data) {
            for(key in data) {
              str = template.replace(/%\w+%/g, function(all) {
                return data[key][all.replace(/%/g, '')] || "";
              });
              str = str.replace(/data\-/g, '')
              $('#list tr:last').after('<tr>' + str + '</tr>');
            }
            // We need to push a state in history for the browsers to behave correctly on 'back' buttons
            history.pushState(null,"", window.location.href.replace(/#page=([^&]*)/,'') + "#page=" + page);
            if (page * objects_per_page > total_objects) {
              $('#more').hide();
              $('#no-more').show();
              canLoad = false;
            } else {
              $('#more').show();
              canLoad = true;
            }
            $('#loading').hide();
          }
        });
      
      }

    }

  });

});