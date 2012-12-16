$(document).ready(function(){
  
    /* 
     * Editable elements : basics 
     */
    $('.editable').editable({
        pk: 1,
        placement: 'bottom'
    });

    /* Editable lists of ul/li 
     * (for skills for instance)
     */
    $('.editable-li').attr('data-value', $.map( $('.editable-li li'), function (element) { return $(element).attr('rel') }).join(',') );
    $('.editable-li').editable({
        pk: 1,
        placement: 'bottom',
        display:   function(value, sourceData) {
                        $(this).empty();
                        var selected = $.grep(sourceData,function(e,i){
                          return (value.indexOf(e.value) != -1);
                        });
                        for(item in selected){
                          $(this).append('<li class="label" rel="' + selected[item].value + '">' + selected[item].text + '</li>');
                        }
                    }
    });

    /* For manual toggles */
    $('.editable-trigger').click(function(e) {
      e.stopPropagation();
      if (e.target.tagName == 'I') // icon ...
        target = e.target.parentNode.getAttribute('data-target');
      else // a or span
        target = e.target.getAttribute('data-target');
      $('.' + target + '-target').editable('toggle');
    });

    /*  Markdown fields
     *  (for about)
     */ 
    var converter = Markdown.getSanitizingConverter();
    var editor = new Markdown.Editor(converter);
    editor.run();
  
    // Save function
    $('#wmd-save').click(function() {
      $(".wmd-message").html("Saving to server ...");
      $.post($('#wmd-input').attr('data-url'), {
        name: $('#wmd-input').attr('data-name'),
        value: $('#wmd-input').val()
      })
      .success(function(data, config) {
         $(".wmd-message").html("Changes saved.");               
      })
      .error(function(errors) {
         $(".wmd-message").html("Error saving changes.");
      });
    });

    // Editable triggers for markdown
    $('.markdown-trigger').click(function(){
      $('.wmd-wrapper').toggle();
    });
});
