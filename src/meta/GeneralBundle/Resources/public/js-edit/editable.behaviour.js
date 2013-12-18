$(document).ready(function(){

    /*
     * Editables
     */
    timers = {};
    saveDelay = 1000; // milliseconds

    var saveData = function(dataArray) {
      //console.log('saving data "' + dataArray["value"] + '" for name "' + dataArray["name"] + '"!');

      clearInterval(timers[dataArray["name"]]); // Clearing before sending the post request
      $.post(dataArray["url"], {
        name: dataArray["name"],
        value: dataArray["value"]
      })
      .success(function(data, config) {
        $("[data-name=" + dataArray["name"] + "]").attr("data-last", dataArray["value"]);
        setFlash("success", "yeah");           
      })
      .error(function(errors) {
        $("[data-name=" + dataArray["name"] + "]").html($("[data-name=" + dataArray["name"] + "]").attr("data-last"));
        setFlash("error", "hoho");
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

    $('[contenteditable=true]').on("keypress", function(e) {
      if (e.which == '13'){ e.preventDefault(); }
    });

    $('[contenteditable=true]').on("keyup", function() {
      name = $(this).attr("data-name");
      url = $(this).attr("data-url");
      last = $(this).attr("data-last");
      value = $.trim($(this).text());
      catchChange({url: url, name: name, last: last, value: value});
    });

    /* 
     * Text area editables
     */
    var textareaCallback = function(data) {
      target = $(data.$el[0]); // The textarea
      name = target.attr("data-name");
      url = target.attr("data-url");
      last = target.attr("data-last");
      value = data.getCode();
      catchChange({url: url, name: name, last: last, value: value});
    }
    var test = $('textarea[contenteditable=true]').redactor({
      air:true,
      airButtons: ['formatting', '|', 'bold', 'italic', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|',
                                        'image', 'video', 'file', 'table', 'link'],
      keyupCallback: textareaCallback,
      execCommandCallback: textareaCallback
    });




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

    $('span[data-name=frequency').on('save', function(e, params) {
      if(params.newValue != 1){ // NOT daily
        $(".specificDay").show();
      } else {
        $(".specificDay").hide();
      }
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
