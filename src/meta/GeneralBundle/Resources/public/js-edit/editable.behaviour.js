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
    if ($('#wmd-input')) {
      var converter = Markdown.getSanitizingConverter();
      var editor = new Markdown.Editor(converter);
      editor.run();
    }

    if ($('#wmd-input-second')) {
      // In case there is a second one
      var converter2 = new Markdown.Converter();
      var editor2 = new Markdown.Editor(converter2, "-second");
      editor2.run();
    }

    // Save function
    $('#wmd-save, #wmd-save-second').click(function() {

      var messagesBox = $(this).parent().parent().find(".wmd-message");
      var inputBox = $(this).parent().parent().find('.wmd-input');

      messagesBox.html("Saving to server ...");

      $.post(inputBox.attr('data-url'), {
        name: inputBox.attr('data-name'),
        value: inputBox.val()
      })
      .success(function(data, config) {
         messagesBox.html("Changes saved.");               
      })
      .error(function(errors) {
         messagesBox.html("Error saving changes.");
      });

    });

    // Editable triggers for markdown
    $('.markdown-trigger').click(function(){

      var markdownBox = $(this).parent().parent().find('.wmd-wrapper');

      markdownBox.toggle();

    });
});
