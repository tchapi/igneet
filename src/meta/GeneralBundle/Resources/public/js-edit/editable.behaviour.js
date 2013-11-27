$(document).ready(function(){
  
    /* Editable elements : basics 
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
                    if (sourceData) {
                      var selected = $.grep(sourceData,function(e,i){
                        return (value.indexOf(e.value) != -1);
                      });
                      $(this).empty();
                      var len = selected.length;
                      for(var i=0; i<len; i++){
                        $(this).append('<li class="label label-default" rel="' + selected[i].value + '">' + selected[i].text + '</li>');
                      }
                    } else {
                      if (value){
                        var len = value.length;
                        for(var i=0; i<len; i++){
                          $(this).append('<li class="label label-default">' + value[i] + '</li>');
                        }
                      }
                    }
                  },
        select2: {
          tags:[],
          tokenSeparators: [","]
        }
    });

    /* Overriding display function for editable-server-response items (such as list items)
     */
    $('.editable-server-response').editable('option', 'display', 
      function(value, response){
          if (response.length > 0){
            console.log(response);
            $(this).html(response);
          }
        }
    );

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

    // Save function with states
    $('.wmd-input[unsaved="no"]').keyup(function(){
      $(this).parent().parent().find(".wmd-message").html('<span class="alert alert-warning">' + Translator.get('alert.unsaved.changes') + '</span>');
      $(this).attr('unsaved', 'yes');
    });

    $('#wmd-save, #wmd-save-second').click(function() {

      var containerBox = $(this).parent().parent();
      var messagesBox = containerBox.find('.wmd-message');
      var inputBox = containerBox.find('.wmd-input');
      var contentBox = containerBox.parent().parent().find('.content');

      messagesBox.html('<span class="alert alert-info">' + Translator.get('alert.saving.server') + '</span>');

      $.post(inputBox.attr('data-url'), {
        name: inputBox.attr('data-name'),
        value: inputBox.val()
      })
      .success(function(data, config) {
         messagesBox.html('<span class="alert alert-success">' + Translator.get('alert.changes.saved.at', { 'date' : (new Date()).toTimeString() }) + '</span>');
         window.setTimeout(function(){ if (inputBox.attr('unsaved') == 'no') { messagesBox.html(Translator.get('alert.click.save.changes')); } }, 3000);
         inputBox.attr('unsaved', 'no');
         contentBox.html(data);             
      })
      .error(function(errors) {
         messagesBox.html('<span class="alert alert-danger">' + Translator.get('alert.error.saving.changes') + '</span>');
      });

    });

    // Editable triggers for markdown
    $('.markdown-trigger').click(function(){

      var containerBox = $(this).parent().parent();
      var markdownBox = containerBox.find('.wmd-wrapper');
      var messageBoxTop = containerBox.find('.wmd-message-top');
      var inputBox = containerBox.find('.wmd-input');
      var contentBox = markdownBox.parent().find('.content');

      if (inputBox.is(":visible") && inputBox.attr('unsaved') == 'yes'){
        messageBoxTop.show();
      } else {
        messageBoxTop.hide();
      }

      markdownBox.toggle();
      contentBox.toggle();

    });

    /* Delete behaviours
     * to catch and two-stepize deletion
     */
    $('a[data-confirm]').click(function(ev) {
      
      var modal = '<div id="dataConfirmModal" class="modal fade">' +
                    '<div class="modal-dialog">' +
                      '<div class="modal-content">' +
                        '<div class="modal-header">' +
                          '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' +
                          '<h4 class="modal-title" id="dataConfirmLabel">' + Translator.get('alert.please.confirm') + '</h4>' +
                        '</div>' +
                        '<div class="modal-body">' +
                        '</div>' +
                        '<div class="modal-footer">' +
                          '<button type="button" class="btn btn-default" data-dismiss="modal">' + Translator.get('cancel') + '</button>' +
                          '<button type="button" class="btn btn-primary" id="dataConfirmOK">' + Translator.get('ok') + '</button>' +
                        '</div>' +
                      '</div><!-- /.modal-content -->' +
                    '</div><!-- /.modal-dialog -->' +
                  '</div><!-- /.modal -->';

      var href = $(this).attr('href');
      if (!$('#dataConfirmModal').length) {
        $('body').append(modal);
      } 
      $('#dataConfirmModal').find('.modal-body').text($(this).attr('data-confirm'));
      $('#dataConfirmOK').click(function(){
        document.location.href = href;
      });
      $('#dataConfirmModal').modal({show:true});
      
      return false;

    });

    /*
     * Settings page : trigger display
     */
    $('#enableDigest').change(function(){
      $('.digest').toggle();
      $.post($(this).attr('data-url'), {
        name: $(this).attr('data-name'),
        value: $(this).is(':checked')?1:0
      }, function(){
        setFlash('success', Translator.get('user.settings.saved'));
      });
    });

    $('#specificDay').change(function(){
      $('.specificDayChoice').toggle();
      $.post($(this).attr('data-url'), {
        name: $(this).attr('data-name'),
        value: $(this).is(':checked')?1:0
      }, function(){
        setFlash('success', Translator.get('user.settings.saved'));
      });
    });

    $('#specificEmails').change(function(){
      $('.specificEmailsChoice').toggle();
      $.post($(this).attr('data-url'), {
        name: $(this).attr('data-name'),
        value: $(this).is(':checked')?1:0
      }, function(){
        setFlash('success', Translator.get('user.settings.saved'));
      });
    });

});
