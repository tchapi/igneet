/*global $,Translator,alertify */
/*jslint browser: true*/
$(document).ready(function() {

    /* Helper function for displaying alerts after $.post */
    window.process = function(data, type, defaultMessage) {

        if (data !== null) {
            if (data.redirect) {
                // We must reload the page
                window.location.replace(data.redirect);
                return;
            }
            defaultMessage = data.message || defaultMessage;
        }

        alertify.log(defaultMessage, type);

    };

    /*
     * Responsive slide menu
     */
    var $menu_trigger = $(".menu-trigger");
    if ($menu_trigger !== undefined) {
        $menu_trigger.on('click', function(e) {
            e.preventDefault();
            if ($("body").hasClass('menu-active')) {
                $("body").removeClass('menu-active');
            } else {
                $("body").addClass('menu-active');
            }
        });
    }

    /* 
     * Sub menu / Dropdowns
     */
    $("li.dropdown > a").on('click', function(e) {
        if (!($("nav[role=mobile]").is(':visible'))) {
            $(this).parent().toggleClass("active").find("ul").toggle().focus();
        }
        e.preventDefault();
    });

    $("li.dropdown > ul").focusout(function() {
        if (!($("nav[role=mobile]").is(':visible'))) {
            window.setTimeout(function() {
                if ($(document.activeElement).parents('.active').length === 0) {
                    $("li.dropdown").removeClass("active").find("ul").hide();
                }
            }, 1);
        }
    });

    /*
     * Dismiss & cookies
     */
    $("[dismiss]").on('click', function(e) {
        var targetId = $(this).attr('dismiss');
        $("#" + targetId).slideUp();
        if (document.cookie.indexOf("igneet_dismiss[" + targetId + "]") === -1) {
            alertify.log(Translator.trans('guide.dismissed'), "info");
        }
        document.cookie = "igneet_dismiss[" + targetId + "]=true; path=/; expires=Wed, 1 Jan 2020 00:42:42 UTC;";
        e.preventDefault();
    });
    $("[dismiss-reset=all]").on('click', function(e) {
        document.cookie = "igneet_dismiss[shared_projects]=false; expires=; path=/;";
        alertify.log(Translator.trans('guide.reactivated'), "info");
        e.preventDefault();
        // ** Others ? **
    });

    /*
     * Mark notifications as read
     */
    $("#markRead").click(function() {
        $.post($(this).attr('data-url'))
            .done(function(data) {
                if (data.redirect) {
                    window.location.replace(data.redirect);
                }
            })
            .fail(function() {
                alertify.error(Translator.trans('alert.error.saving.changes'));
            });
    });

    // Choose a user - select box that allows for a choice not in the community
    $("select#communityUsername").change(function() {
        if ($(this).val() === -1) {
            $("input#mailOrUsername").prop('disabled', false);
        } else {
            $("input#mailOrUsername").prop('disabled', true);
        }
    });

    // Count notifications
    $.post($("#notificationsCount").attr('data-update-path'), function(data) {

        $("i[role=loading]").remove();
        $("i[role=loaded]").show();
        $(".notificationsCount").html(data).show();

    });

    // Select all of shortcode when visible
    $("#shortcode-trigger").click(function(e) {
        $("#shortcode").fadeToggle(200, function() {

            if ($("#shortcode").is(":visible")) {
                $("#shortcode input").focus();
                $("#shortcode input:text").select();
            } else {
                $("#shortcode input").blur();
            }

        });
        e.preventDefault();
    });


    // Open the search field
    $("#search-trigger").click(function(e) {
        $("#search").fadeToggle(200, function() {

            if ($("#search").is(":visible")) {
                $("#search input").focus();
            } else {
                $("#search input").blur();
            }

        });
        e.preventDefault();
    });

    // Prevents empty search term
    $("#search").submit(function(e) {
        if ( $.trim($("#search input").val()) == "") {
            e.preventDefault();
        }
    });

    // Toggle index (pages) view
    $(".tree > .toggle").click(function(e) {
        $(this).parent().toggleClass("open");
        var flavour = $(this).parent().attr('data-trees');
        e.preventDefault();
        document.cookie = "igneet_trees_open[" + flavour + "]=" + ($(this).parent().hasClass('open') ? "true" : "false") + "; path=/; expires=Wed, 1 Jan 2020 00:42:42 UTC;";
    });
    
    /*
     * Watching / unwatching
     */
    $(document).on('click', '.watch, .follow', function(e) {
        e.preventDefault();
        var _self = $(this);
        _self.parent().find('.working').show();
        $.post(_self.attr('href'))
            .done(function(data) {
                _self.parent().html(data.div);
                process(data, "success", Translator.trans('alert.changes.saved'));
            })
            .fail(function(xhr) {
                process(xhr.responseJSON, "error", Translator.trans('alert.error.saving.changes'));
            })
            .always(function(){
                _self.parent().find('.working').fadeOut();
            });
    });

    /*
     * Resources pages : see details
     */
    $('ul.resources > li').on('click', function(e) {
        if ($(e.target).parents('.details').length === 0) { // Make sure we're not bubbling too much
            e.preventDefault();
            _self = $(this).hasClass("detailed");
            $('.detailed').removeClass('detailed');
            if (!_self) $(this).toggleClass('detailed');
        }
    }).children("a").click(function(e) {
        e.stopPropagation(); // Download and view buttons
    });

    // Scroll to resource
    if ($(".detailed").length) {
        $('html,body').animate({
            scrollTop: $(".detailed").offset().top - 65
        });
    }

    // Announcements 
    $(".announcements .close").on('click', function(e) {
        var target = $(this).parent();
        $.post(target.attr('data-url'), function() {
            target.fadeOut();
        });
        e.preventDefault();
    });

    /* Delete behaviours
     * to catch and two-stepize deletion
     */
    $('a[data-confirm]').on('click', function(ev) {

        var href = $(this).attr('href');
        var text = $(this).attr('data-confirm') || Translator.trans('alert.please.confirm');

        // Custom alert box 
        alertify.set({
            labels: {
                ok: Translator.trans('ok'),
                cancel: Translator.trans('cancel')
            }
        });
        alertify.set({
            buttonFocus: "cancel"
        });

        alertify.confirm(text, function(e) {
            if (e) {
                document.location.href = href;
            }
        });

        return false;

    });
});