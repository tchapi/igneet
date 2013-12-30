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

  var retrieveResults = function(page, full, callback) {
    
    $('#loading').show();
    $('#more').hide();
    canLoad = false; // While we're doing this, prevent other calls

    $.ajax({
      type: "POST",
      url: url,
      data: {page: page, full: full},
      success: function(data) {
        
        for(key in data) {
          // Let's replace the placeholders first
          str = template.replace(/%\w+%/g, function(all) {
            return data[key][all.replace(/%/g, '')] || "";
          });
          str = str.replace(/data\-/g, ''); // Replace data-href and the like with href in the template
          $('#list tr:last').after('<tr>' + str + '</tr>');
        }
        // Are we at the end of the results ?
        if (page * objects_per_page > total_objects) {
          $('#no-more').show();
          canLoad = false;
        } else {
          $('#more').show();
          canLoad = true;
        }
        $('#loading').hide();
        if (callback) callback();
      }
    });

  }

  $('#loading').hide();
  $('#no-more').hide();

  page = 1;

  canLoad = true; // Infinite scrolling is enabled
  
  var template = $('#list tr.template').html(); // We get the template for the infinite scrolling

  // Handle urls like this : /[objects]#page=N
  gotoPage = location.hash.match(new RegExp('page=([^&]*)'));
  gotoPage = gotoPage?gotoPage[1]:1;

  // If we have a hash that tells us to go to a certain page, we need to request the missing elements
  if (gotoPage !== 1 && (gotoPage-1)*objects_per_page < total_objects) {

    // Load intermediary pages (full = true)
    retrieveResults(gotoPage,true, null, function(){
      // Finally, update the page parameter for the next calls
      page = gotoPage;
      // Scroll to  ? FIX ME /!\
    });

  }

  // When we scroll
  $( window ).scroll(function() {

    if (canLoad && $("#more").isOnScreen()) {

      page++;

      retrieveResults(page,false, function(){
        history.pushState(null,"", window.location.href.replace(/#page=([^&]*)/,"#page=" + page));
      });
      
    }

  });

});