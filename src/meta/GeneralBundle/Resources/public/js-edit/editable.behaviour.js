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
    $('.editable-li').each(function(){
      $(this).attr('data-value', $.map( $(this).find('li'), function (element) { return $(element).attr('rel') }).join(',') );
    });

    $('.editable-li').editable({
        pk: 1,
        placement: 'bottom',
        display:  function(value, sourceData) {
                    $(this).empty();
                      if (sourceData) {
                        var selected = $.grep(sourceData,function(e,i){
                          return (value.indexOf(e.value) != -1);
                        });
                        for(item in selected){
                          $(this).append('<li class="label" rel="' + selected[item].value + '">' + selected[item].text + '</li>');
                        }
                      } else {
                        for(item in value){
                          $(this).append('<li class="label">' + value[item] + '</li>');
                        }
                      }
                  },
        select2: {
          tags:[]
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
    if ($('#wmd-input').length != 0) {
      var converter = Markdown.getSanitizingConverter();
      var editor = new Markdown.Editor(converter);
      editor.run();
    }

    if ($('#wmd-input-second').length != 0) {
      // In case there is a second one
      var converter2 = new Markdown.Converter();
      var editor2 = new Markdown.Editor(converter2, "-second");
      editor2.run();
    }

    // Save function
    $('#wmd-save, #wmd-save-second').click(function() {

      var messagesBox = $(this).parent().parent().find(".wmd-message");
      var inputBox = $(this).parent().parent().find('.wmd-input');
      var contentBox = $(this).parent().parent().parent().parent().find('.content');

      messagesBox.html("Saving to server ...");

      $.post(inputBox.attr('data-url'), {
        name: inputBox.attr('data-name'),
        value: inputBox.val()
      })
      .success(function(data, config) {
         messagesBox.html("Changes saved.");
         console
         contentBox.html(data);             
      })
      .error(function(errors) {
         messagesBox.html("Error saving changes.");
      });

    });

    // Editable triggers for markdown
    $('.markdown-trigger').click(function(){

      var markdownBox = $(this).parent().parent().find('.wmd-wrapper');
      var contentBox = markdownBox.parent().find('.content');
      markdownBox.toggle();
      contentBox.toggle();

    });
});
