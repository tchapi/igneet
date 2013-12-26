$(document).ready(function(){

  /*
   * Editables
   */
  timers = {};
  saveDelay = 1000; // milliseconds

  var saveData = function(dataArray) {

    var process = function(data, defaultMessage) {
      if (data) {
        try { 
          data = JSON.parse(data); 
          if (data.redirect) {
            window.location.replace(data.redirect);
          } else {
            alertify.success(data.message);
          }
        } catch(err) { // In case the data is not JSON, we pass
          alertify.success(defaultMessage);
        }
      } else {
        alertify.success(defaultMessage);
      }
    };

    clearInterval(timers[dataArray["name"]]); // Clearing before sending the post request
    $.post(dataArray["url"], {
      name: dataArray["name"],
      key: dataArray["key"],
      value: dataArray["value"]
    })
    .success(function(data, config) {
      $("[data-name=" + dataArray["name"] + "]").attr("data-last", dataArray["value"]);
      process(data, Translator.get('alert.changes.saved'));
    })
    .error(function(errors) {
      $("[data-name=" + dataArray["name"] + "]").html($("[data-name=" + dataArray["name"] + "]").attr("data-last"));
      process(data, Translator.get('alert.error.saving.changes'));
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
      if (e.which == '13'){  // Prevents the Return to be inserted
        e.preventDefault();
      }
    })
    .on("keyup", function(e) {
      name = $(this).attr("data-name");
      key = $(this).attr("data-key");
      url = $(this).attr("data-url");
      last = $(this).attr("data-last");
      value = $.trim($(this).text());
      if (e.which == '13'){ // Trigger a save with the Return key
        e.preventDefault(); 
        clearInterval(timers[name]);
        if (last !== value) { saveData({url: url, name: name, key: key, last: last, value: value}); }
      } else {
        catchChange({url: url, name: name, key: key, last: last, value: value});
      }
    })
    .on('paste', function (e) { // Prevents insertion of markup
      if (document.queryCommandEnabled('inserttext')) {
        e.preventDefault();
        var pastedText = prompt(Translator.get('paste.something'));
        if (pastedText !== null){
          document.execCommand('inserttext', false, $.trim(pastedText));
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
      air: true,
      emptyHtml: '<p><em>...</em><br /></p>',
      minHeight: 100, // To allow PASTE event - ARGHHHH I hate you Chrome
      airButtons: ['formatting', '|', 'bold', 'italic', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|',
                                        'image', 'video', 'file', 'table', 'link'],
      keyupCallback: richareaCallback,
      execCommandCallback: richareaCallback
    });
  }


  /* Delete behaviours
   * to catch and two-stepize deletion
   */
  $('a[data-confirm]').click(function(ev) {

    var href = $(this).attr('href');
    var text = $(this).attr('data-confirm') || Translator.get('alert.please.confirm');

    // Custom alert box 
    alertify.set({ labels: { ok: Translator.get('ok'), cancel: Translator.get('cancel') } });
    alertify.set({ buttonFocus: "cancel" });

    alertify.confirm(text, function (e) {
      if (e) { document.location.href = href; }
    });

    return false;

  });

  /*
   * Settings page : trigger display
   */
  $('#enableDigest').change(function(){ $('.digest').toggle(); });
  $('select[data-name="frequency"]').change(function(){
    if ($(this).val() == '1') { // daily
      $('.specificDay').hide();
    } else {
      $('.specificDay').show();
    }
  });
  $('#specificDay').change(function(){ $('.specificDayChoice').toggle(); });
  $('#specificEmails').change(function(){ $('.specificEmailsChoice').toggle(); });

});
