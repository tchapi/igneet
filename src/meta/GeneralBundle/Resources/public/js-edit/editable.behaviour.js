$(document).ready(function(){

    /*
     * Editables
     */
    timers = {};
    saveDelay = 1000; // milliseconds

    var saveData = function(dataArray) {

      clearInterval(timers[dataArray["name"]]); // Clearing before sending the post request
      $.post(dataArray["url"], {
        name: dataArray["name"],
        key: dataArray["key"],
        value: dataArray["value"]
      })
      .success(function(data, config) {
        $("[data-name=" + dataArray["name"] + "]").attr("data-last", dataArray["value"]);
        alertify.success( "[TR [CHANGES SAVED] /TR]" );           
      })
      .error(function(errors) {
        $("[data-name=" + dataArray["name"] + "]").html($("[data-name=" + dataArray["name"] + "]").attr("data-last"));
        alertify.error( "[TR [ERROR SAVING CHANGES] /TR]" );
      });

    };

    var createInterval = function(f, parameters, interval) {
      return setInterval(function() { f(parameters); }, interval);
    } 

    var catchChange = function(dataArray) {
      if (dataArray["last"] !== dataArray["value"]) {
        clearInterval(timers[dataArray["name"]]);
        timers[dataArray["name"]] = createInterval(saveData, dataArray, saveDelay);
      }
    };

    $('[contenteditable=true][rich=false]')
      .on("keypress", function(e) {
        if (e.which == '13'){  // Trigger a save with the Return key
          e.preventDefault(); 
          name = $(this).attr("data-name");
          key = $(this).attr("data-key");
          url = $(this).attr("data-url");
          last = $(this).attr("data-last");
          value = $.trim($(this).text());
          clearInterval(timers[name]);
          saveData({url: url, name: name, key: key, last: last, value: value});
        }
      })
      .on("keyup", function() {
        name = $(this).attr("data-name");
        key = $(this).attr("data-key");
        url = $(this).attr("data-url");
        last = $(this).attr("data-last");
        value = $.trim($(this).text());
        catchChange({url: url, name: name, key: key, last: last, value: value});
      })
      .on('paste', function (e) { // Prevents insertion of markup
        if (document.queryCommandEnabled('inserttext')) {
          e.preventDefault();
          var pastedText = prompt(' /TR Paste something. /TR '); // TODO : to translate !
          if (pastedText !== null){
            document.execCommand('inserttext', false, pastedText);
          }
        }
      });

    /* 
     * Select box editables
     */
    $('select').change(function(){
      name = $(this).attr("data-name");
      key = $(this).attr("data-key");
      url = $(this).attr("data-url");
      last = $(this).attr("data-last");
      value = $.trim($(this).val());
      catchChange({url: url, name: name, key: key, last: last, value: value});
    });

    /* 
     * Checkbox editables
     */
    $('input[type="checkbox"]').change(function(){
      name = $(this).attr("data-name");
      key = $(this).attr("data-key");
      url = $(this).attr("data-url");
      last = $(this).attr("data-last");
      value = $(this).is(':checked')?1:0
      catchChange({url: url, name: name, key: key, last: last, value: value});
    });

    /* 
     * Text area editables
     */
    if ($('[contenteditable=true][rich=true]').length > 0) {
      var richareaCallback = function(data) {
        target = data.$editor; // The textarea
        name = target.attr("data-name");
        key = target.attr("data-key");
        url = target.attr("data-url");
        last = target.attr("data-last");
        value = data.getCode();
        catchChange({url: url, name: name, key: key, last: last, value: value});
      }
      $('[contenteditable=true][rich=true]').redactor({
        air:true,
        minHeight: 100, // To allow PASTE event - ARGHHHH I hate you Chrome
        airButtons: ['formatting', '|', 'bold', 'italic', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|',
                                          'image', 'video', 'file', 'table', 'link'],
        keyupCallback: richareaCallback,
        execCommandCallback: richareaCallback
      });
    }


/* ------------- OLD ---------------- */

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
    $('#enableDigest').change(function(){ $('.digest').toggle(); });
    $('#specificDay').change(function(){ $('.specificDayChoice').toggle(); });
    $('#specificEmails').change(function(){ $('.specificEmailsChoice').toggle(); });

});
