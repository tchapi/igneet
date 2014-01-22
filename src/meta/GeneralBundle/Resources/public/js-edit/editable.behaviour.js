$(document).ready(function(){

  /*
   * Editables
   */
  timers = {};
  saveDelay = 1000; // milliseconds

  process = function(data, type, defaultMessage) {
    if (data) {
      try { 
        data = JSON.parse(data); 
        if (data.redirect) {
          window.location.replace(data.redirect);
        } else if (data.message) {
          alertify.log(data.message, type);
        } else {
          alertify.log(defaultMessage, type);
        }
      } catch(err) { // In case the data is not JSON, we pass
        alertify.log(defaultMessage, type);
      }
    } else {
      alertify.log(defaultMessage, type);
    }
  };

  var saveData = function(dataArray, callback) {

    clearInterval(timers[dataArray["name"]]); // Clearing before sending the post request
    $.post(dataArray["url"], {
      name: dataArray["name"],
      key: dataArray["key"],
      value: dataArray["value"]
    })
    .success(function(data, config) {
      if (dataArray["name"] != 'tags' && dataArray["name"] != 'skills') {
        $("[data-name=" + dataArray["name"] + "]").attr("data-last", dataArray["value"]);
      }
      process(data, "success", Translator.trans('alert.changes.saved'));
      if (callback) callback(data);
    })
    .error(function(data) {
      if (dataArray["name"] != 'tags' && dataArray["name"] != 'skills') {
        $("[data-name=" + dataArray["name"] + "]").html($("[data-name=" + dataArray["name"] + "]").attr("data-last"));
      }
      process(data, "error", Translator.trans('alert.error.saving.changes'));
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
        if (last !== value) { saveData({url: url, name: name, key: key, value: value}); }
      } else {
        catchChange({url: url, name: name, key: key, last: last, value: value});
      }
    })
    .on('paste', function (e) { // Prevents insertion of markup
      if (document.queryCommandEnabled('inserttext')) {
        e.preventDefault();
        var pastedText = prompt(Translator.trans('paste.something'));
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
  if ($('[contenteditable=true][rich=true],[contenteditable=true][rich=full]').length > 0) {
    var richareaCallback = function(data) {
      target = data.$editor; // The textarea
      name = target.attr("data-name");
      key = target.attr("data-key");
      url = target.attr("data-url");
      last = target.attr("data-last");
      value = data.getCode();
      catchChange({url: url, name: name, key: key, last: last, value: value});
    }
    // Standard rich text : bar floats in the air
    $('[contenteditable=true][rich=true]').redactor({
      air: true,
      emptyHtml: '<p>...<br /></p>',
      minHeight: 100, // To allow PASTE event - ARGHHHH I hate you Chrome
      airButtons: ['formatting', '|', 'bold', 'italic', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|',
                                        'image', 'video', 'file', 'table', 'link'],
      keyupCallback: richareaCallback,
      execCommandCallback: richareaCallback
    });
    // Wiki-style rich text : bar is attached
    $('[contenteditable=true][rich=full]').redactor({
      emptyHtml: '<p></p>',
      minHeight: 100, // To allow PASTE event - ARGHHHH I hate you Chrome
      buttons: ['html', '|', 'formatting', '|', 'bold', 'italic', 'underline', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|',
          'image', 'video', 'file', 'table', 'link', '|',
          'fontcolor', 'backcolor', '|', 'horizontalrule'], 
      keyupCallback: richareaCallback,
      execCommandCallback: richareaCallback
    });
  }

  /*
   * ul / li editables : skills and tags
   */
  var displayResults = function(results, element) {
    element.find('ul').remove();
    element.append('<ul id="results"></ul>');
    for (i = 0; i < Math.min(10,results.length); i++) {
      $("#results").append("<li rel='" + results[i].value + "' style='border: 1px solid #" + results[i].color + ";'>" + results[i].text+"</li>");
    }
  }
  var display = function(triggerElement, boolean){
    if (boolean){
      // Shows the input
      triggerElement.next('span').show().find('input').val("").focus();
      triggerElement.hide();
    } else {
      // Hides the input
      triggerElement.parent().hide();
      triggerElement.parent().parent().find('a.add').show();
      // Removes the list
      if ($("ul#results").length > 0) { $("ul#results").remove(); }
    }
  }
  // Remove an element
  $("ul[contenteditable=list] > li > a.remove").on('click', function(e){
    e.preventDefault();
    target = $(this).parent();
    name = target.parent().attr("data-name");
    key = target.attr("rel");
    url = target.parent().attr("data-url");
    value = "remove";
    saveData({url: url, name: name, key: key, value: value}, function(){
      target.remove();
    });
  });
  // Add an element
  editableListsData = {};
  $("ul[contenteditable=list] > li > a.add").on('click', function(e){
    e.preventDefault();
    display($(this),true);
    // Gets the list (only for skills)
    if ($(this).attr("data-url") != "") {
      name = $(this).parents('ul').attr("data-name");
      $.getJSON($(this).attr("data-url"), function(data) {
        editableListsData[name] = data;
      });
    }
  });
  $("ul[contenteditable=list] > li > span > a").on('click', function(e){
    e.preventDefault();
    display($(this),false);
  });
  $("ul[contenteditable=list][data-name=skills] > li > span > input")
    .on("keyup", function(e) {
      if (e.which == '13'){
        e.preventDefault();
      } else {
        // For skills, search in the list the correct skill ...
        target = $(this);
        name = target.parents('ul').attr("data-name");
        search = target.val().toLowerCase();
        results = $.grep(editableListsData[name], function(n) {
          return (n.text.toLowerCase().indexOf(search) >= 0 && target.parents('ul').find('li[rel=' + n.value + ']').length == 0);
        })
        displayResults(results, target.parent().parent());
      }
    });
    $("ul[contenteditable=list][data-name=tags] > li > span > input")
    .on("keyup", function(e) {
      if (e.which == '13'){ // Trigger a save with the Return key for tags
        e.preventDefault();
        target = $(this).parents('ul');
        name = target.attr("data-name");
        key = $(this).val();
        url = target.attr("data-url");
        value = "add";
        saveData({url: url, name: name, key: key, value: value}, function(data){
          try { data = JSON.parse(data); color = " style='border: 1px solid #" + data.color + ";'"; } catch(e) { color = ""; }
          target.children().last().before("<li" + color + "><a href='#' class='remove'><i class='fa fa-times'></i></a>" + key + "</li>");
          display(target.find('li > span > a'),false);
        });
      }
    });
  // We bind to document because we don't have the element yet
  $(document).on('click', "ul#results li", function(){
    target = $(this).parent('ul').parents('ul');
    name = target.attr("data-name");
    key = $(this).attr("rel");
    url = target.attr("data-url");
    value = "add";
    saveData({url: url, name: name, key: key, value: value}, function(){
      var result = $.grep(editableListsData[name], function(n){ return n.value == key; });
      target.children().last().before("<li rel='" + key + "' style='border: 1px solid #" + result[0].color + ";'><a href='#' class='remove'><i class='fa fa-times'></i></a>" + result[0].text + "</li>");
      display(target.find('li > span > a'),false);
    });
  });

  /* Delete behaviours
   * to catch and two-stepize deletion
   */
  $('a[data-confirm]').click(function(ev) {

    var href = $(this).attr('href');
    var text = $(this).attr('data-confirm') || Translator.trans('alert.please.confirm');

    // Custom alert box 
    alertify.set({ labels: { ok: Translator.trans('ok'), cancel: Translator.trans('cancel') } });
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

  /*
   * Resources pages : see details
   */
  $('ul.resources li > *:not(.details)').on('click', function(e){
    e.preventDefault();
    $('.detailed').removeClass('detailed');
    $(this).parent('li').toggleClass('detailed');
  });

});
