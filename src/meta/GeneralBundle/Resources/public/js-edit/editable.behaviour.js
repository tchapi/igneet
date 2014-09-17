/*global $,Translator,alertify */
/*jslint browser: true*/
$(document).ready(function() {

    var fireEvent = ("ontouchend" in document) ? 'touchend' : 'click';

    // Define: Linkify plugin from stackoverflow
    (function($) {

        "use strict";

        var protocol = 'http://';
        var url1 = /(^|&lt;|\s)(www\..+?\..+?)(\s|&gt;|$)/g,
            url2 = /(^|&lt;|\s)(((https?|ftp):\/\/|mailto:).+?)(\s|&gt;|$)/g,

            linkifyThis = function() {
                var childNodes = this.childNodes,
                    i = childNodes.length;
                while (i--) {
                    var n = childNodes[i];
                    if (n.nodeType === 3) {
                        var html = n.nodeValue;
                        if (html) {
                            html = html.replace(/&/g, '&amp;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(url1, '$1<a href="' + protocol + '$2">$2</a>$3')
                                .replace(url2, '$1<a href="$2">$2</a>$5');

                            $(n).after(html).remove();
                        }
                    } else if (n.nodeType === 1 && !/^(a|button|textarea)$/i.test(n.tagName)) {
                        linkifyThis.call(n);
                    }
                }
            };

        $.fn.linkify = function() {
            this.each(linkifyThis);
        };

    })(jQuery);

    /*
     * Editables
     */
    var timers = {};
    var saveDelay = 1000; // milliseconds

    var saveData = function(dataArray, callback) {

        clearInterval(timers[dataArray.name]); // Clearing before sending the post request

        var posting = $.post(dataArray.url, {
            name: dataArray.name,
            key: dataArray.key,
            value: dataArray.value
        });

        posting.done(function(data) {
            if (dataArray.name !== 'tags' && dataArray.name !== 'skills') {
                $("[data-name=" + dataArray.name + "]").attr("data-last", dataArray.value);
            }
            process(data, "success", Translator.trans('alert.changes.saved'));
            if (callback) {
                callback(data);
            }
        });

        posting.fail(function(xhr) {
            if (dataArray.name !== 'tags' && dataArray.name !== 'skills') {
                $("[data-name=" + dataArray.name + "]").html($("[data-name=" + dataArray.name + "]").attr("data-last"));
            }
            process(xhr.responseJSON, "error", Translator.trans('alert.error.saving.changes'));
        });

    };

    var createInterval = function(f, parameters, callback, interval) {
            return setInterval(function() {
                f(parameters, callback);
            }, interval);
        },

        catchChange = function(dataArray, callback, lazy) {
            if (dataArray.last !== dataArray.value)  {
                clearInterval(timers[dataArray.name]);
                // LAZY intervalling prevents automatic saving, for special cases like the email inputs
                if (!lazy) { timers[dataArray.name] = createInterval(saveData, dataArray, callback, saveDelay); }
            }
        };

    $(document).on('keypress', '[contenteditable=true][rich=false], [contenteditable=true][rich=links]', function(e) {
        if (e.which === 13) { // Prevents the Return to be inserted
            e.preventDefault();
        } else if (e.keyCode === 27) {
            $(this).blur();
            $('.link_choice').remove();
        }
    });


    // Links in list items and wiki pages
    $(document).on(fireEvent, "[contenteditable=true][rich=full], [contenteditable=true][rich=true], [contenteditable=true][rich=links]", function(e) {

        if ($(e.target).closest('a').length) {
            //e.preventDefault();
            $(this).focus();
            var offsets = $(e.target).offset();
            if (e.target.getAttribute('data-provider')) {
                // Resource link
                var open = null;
                if (e.target.getAttribute('data-provider') === "local") {
                    open = '<a href="' + e.target.href + '/download" target="_blank"><i class="fa fa-download"></i> Download Resource</a>';
                } else {
                    open = '<a href="' + e.target.href + '/link" target="_blank"><i class="fa fa-external-link"></i> Open Resource</a>';
                }
                var div = $('<div class="link_choice">' + open + ' | <a href="' + e.target.href + '" target="_blank"><i class="fa fa-pencil"></i> Edit</a></div>').css({
                    "position": "absolute",
                    "left": offsets.left,
                    "top": offsets.top + e.target.offsetHeight + 4
                });
            } else {
                // Standard link
                var div = $('<div class="link_choice"><a href="' + e.target.href + '" target="_blank"><i class="fa fa-external-link"></i> Go to Link</a></div>').css({
                    "position": "absolute",
                    "left": offsets.left,
                    "top": offsets.top + e.target.offsetHeight + 4
                });
            }

            // Remove everything before putting in the new one
            $('.link_choice').remove();
            $(document.body).append(div);

        }

    });
    $(document).on(fireEvent, function(e) {
        if (e.target.className !== "link_choice" && $(e.target).closest('a').length === 0) {
            $('.link_choice').remove();
        }
    });
    $(document).on('keyup', "[contenteditable=true][rich=full], [contenteditable=true][rich=true], [contenteditable=true][rich=links]", function(e) {
        $('.link_choice').remove();
    });

    $(document).on('keyup', '[contenteditable=true][rich=false]', function(e) {
        name = $(this).attr("data-name");
        key = $(this).attr("data-key");
        url = $(this).attr("data-url");
        last = $(this).attr("data-last");
        value = $.trim($(this).text());
        if (e.which === 13) { // Trigger a save with the Return key
            e.preventDefault();
            clearInterval(timers[name]);
            if (last !== value)  {
                saveData({
                    url: url,
                    name: name,
                    key: key,
                    value: value
                });
            }
        } else {
            catchChange({
                url: url,
                name: name,
                key: key,
                last: last,
                value: value
            }, null, $(this).attr("data-name") === "email"); // Care for the email input on the settings page
        }
    });
    $(document).on('paste', '[contenteditable=true][rich=false], [contenteditable=true][rich=links]', function(e) { // Prevents insertion of markup
        if (document.queryCommandEnabled('inserttext')) {
            e.preventDefault();
            var pastedText = prompt(Translator.trans('paste.something'));
            if (pastedText !== null) {
                document.execCommand('inserttext', false, $.trim(pastedText));
            }
        }
    });

    $(document).on('keyup', '[contenteditable=true][rich=links]', function(e) {
        name = $(this).attr("data-name");
        key = $(this).attr("data-key");
        url = $(this).attr("data-url");
        last = $(this).attr("data-last");
        var dummy = $(this).clone();
        dummy.linkify();
        value = $.trim(dummy.html());
        dummy.remove();
        if (e.which === 13) { // Trigger a save with the Return key
            e.preventDefault();
            clearInterval(timers[name]);
            if (last !== value)  {
                saveData({
                    url: url,
                    name: name,
                    key: key,
                    value: value
                }, function(data) {
                    if (data !== null && data.text != e.target.innerHTML) {
                        e.target.innerHTML = data.text;
                    }
                });
            }
        } else {
            catchChange({
                url: url,
                name: name,
                key: key,
                last: last,
                value: value
            }, function(data) {
                if (data !== null && data.text != e.target.innerHTML) {
                    e.target.innerHTML = data.text;
                }
            });
        }
    });

    /* 
     * Select box editables
     */
    $('select').change(function() {
        name = $(this).attr("data-name");
        key = $(this).attr("data-key");
        url = $(this).attr("data-url");
        last = $(this).attr("data-last");
        value = $.trim($(this).val());
        catchChange({
            url: url,
            name: name,
            key: key,
            last: last,
            value: value
        });
    });

    /* 
     * Checkbox editables
     */
    $('input[type="checkbox"]').change(function() {
        name = $(this).attr("data-name");
        key = $(this).attr("data-key");
        url = $(this).attr("data-url");
        last = $(this).attr("data-last");
        value = $(this).is(':checked') ? 1 : 0
        catchChange({
            url: url,
            name: name,
            key: key,
            last: last,
            value: value
        });
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
            catchChange({
                url: url,
                name: name,
                key: key,
                last: last,
                value: value
            });
        }
        // Standard rich text : bar floats in the air
        $('[contenteditable=true][rich=true]').redactor({
            air: true,
            emptyHtml: '<p></p>',
            minHeight: 100, // To allow PASTE event - ARGHHHH I hate you Chrome
            airButtons: ['formatting', '|', 'bold', 'italic', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|',
                'image', 'video', 'file', 'table', 'link'
            ],
            keyupCallback: richareaCallback,
            execCommandCallback: richareaCallback
        });
        // Wiki-style rich text : bar is attached
        $('[contenteditable=true][rich=full]').redactor({
            emptyHtml: '<p></p>',
            minHeight: 100, // To allow PASTE event - ARGHHHH I hate you Chrome
            buttons: ['html', '|', 'formatting', '|', 'bold', 'italic', 'underline', 'deleted', '|', 'unorderedlist', 'orderedlist', 'outdent', 'indent', '|',
                'image', 'video', 'file', 'table', 'link', '|',
                'fontcolor', 'backcolor', '|', 'horizontalrule'
            ],
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
        for (i = 0; i < Math.min(10, results.length); i++) {
            $("#results").append("<li rel='" + results[i].value + "' style='border: 1px solid #" + results[i].color + ";'>" + results[i].text + "</li>");
        }
    },

        displayInput = function(triggerElement, displayBoolean) {
            if (displayBoolean) {
                // Shows the input
                triggerElement.next('span').show().find('input').val("").focus();
                triggerElement.hide();
            } else {
                // Hides the input
                triggerElement.parent().hide();
                triggerElement.parent().parent().find('a.add').show();
                // Removes the list
                if ($("ul#results").length > 0) {
                    $("ul#results").remove();
                }
            }
        };

    // Remove an element
    // We bind to document because we don't have the element yet
    $(document).on('click', "ul[contenteditable=list] > li > a.remove", function(e) {
        e.preventDefault();
        target = $(this).parent();
        name = target.parent().attr("data-name");
        key = target.attr("rel");
        url = target.parent().attr("data-url");
        value = "remove";
        saveData({
            url: url,
            name: name,
            key: key,
            value: value
        }, function() {
            target.remove();
        });
    });

    // Add an element
    editableListsData = {};
    $("ul[contenteditable=list][data-name=skills] > li > a.add").on('click', function(e) {
        e.preventDefault();
        var _self = $(this);
        displayInput(_self, true);
        // Gets the list
        name = _self.closest('ul').attr("data-name");
        $.getJSON(_self.attr("data-url"), function(data) {
            editableListsData[name] = data;
            displayResults(data, _self.parent());
        });
    });
    $("ul[contenteditable=list][data-name=tags] > li > a.add").on('click', function(e) {
        e.preventDefault();
        displayInput($(this), true);
    });
    $("ul[contenteditable=list] > li > span > a").on('click', function(e) {
        e.preventDefault();
        displayInput($(this), false);
    });

    $("ul[contenteditable=list][data-name=skills] > li > span > input")
        .on("keyup", function(e) {
            if (e.which === 13) {
                e.preventDefault();
            } else if (e.keyCode === 27) {
                displayInput($(this), false);
            } else {
                // For skills, search in the list the correct skill ...
                target = $(this);
                name = target.parents('ul').attr("data-name");
                search = target.val().toLowerCase();
                results = $.grep(editableListsData[name], function(n) {
                    return (n.text.toLowerCase().indexOf(search) >= 0 && target.parents('ul').children('li[rel=' + n.value + ']').length == 0);
                })
                displayResults(results, target.parent().parent());
            }
        });

    $("ul[contenteditable=list][data-name=tags] > li > span > input")
        .on("keyup", function(e) {
            target = $(this).closest('ul');
            target.find('.thinking').hide();
            target.find('.cancel').show();
            if (e.which === 13 && $.trim($(this).val()) != "") { // Trigger a save with the Return key for tags
                e.preventDefault();
                target.find('.thinking').show();
                target.find('.cancel').hide();
                name = target.attr("data-name");
                key = $(this).val();
                url = target.attr("data-url");
                value = "add";
                saveData({
                    url: url,
                    name: name,
                    key: key,
                    value: value
                }, function(data) {
                    target.children().last().before(data.tag);
                    target.find('li > span > input').val("").focus(); // to allow multiple tags entry
                    target.find('.thinking').hide();
                    target.find('.cancel').show();
                });
            } else if (e.keyCode === 27) {
                displayInput(target.find('li > span > a'), false);
            }
        });

    // We bind to document because we don't have the element yet
    $(document).on('click', "ul#results li", function() {
        target = $(this).parent('ul').parent().closest('ul');
        name = target.attr("data-name");
        key = $(this).attr("rel");
        url = target.attr("data-url");
        value = "add";
        saveData({
            url: url,
            name: name,
            key: key,
            value: value
        }, function(data) {
            target.children().last().before(data.skill);
            displayInput(target.find('li > span > a'), false);
        });
    });

    /*
     * Slip / Nestable
     */

    // Add new item in the list
    $(".tree .new, .none .new").click(function(e) {

        e.preventDefault();
        alertify.prompt($(this).attr('data-title'), $.proxy(function(e, str) {
            // str is the input text
            if (e) {
                window.location.replace($(this).attr('data-url') + '&' + $.param({
                    'title': str,
                    'parent': $(".tree .active").attr('id')
                }));
            }
        }, this), null);

    });

    // Remove item from the list
    $(".tree .remove").click(function(e) {

        e.preventDefault();
        var item = $(this).parent().parent('li');
        alertify.confirm($(this).attr('data-title'), $.proxy(function(e) {
            if (e) {
                $.post($(this).attr('data-url'), {
                    'uid': $(".tree .active").parents('li').attr('id')
                })
                    .success(function(data) {
                        process(data, "success", Translator.trans('alert.changes.saved'));
                    })
                    .error(function(data) {
                        process(data, "error", Translator.trans('alert.error.saving.changes'));
                    });
            }
        }, this));

    });

    /*
     * Settings page : trigger display
     */
    $('#enableDigest').change(function() {
        $('.digest').toggle();
    });
    $('select[data-name="frequency"]').change(function() {
        if ($(this).val() == '1') { // daily
            $('.specificDay').hide();
        } else {
            $('.specificDay').show();
        }
    });
    $('#specificDay').change(function() {
        $('.specificDayChoice').toggle();
    });
    $('#specificEmails').change(function() {
        $('.specificEmailsChoice').toggle();
    });

});