/*global $,Translator,alertify */
/*jslint browser: true*/
$(document).ready(function() {

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

    /*
     * Resources pages : see details
     */
    $('ul.resources > li').on('click', function(e) {
        if($(e.target).parents('.details').length === 0) { // Make sure we're not bubbling too much
            e.preventDefault();
            _self = $(this).hasClass("detailed");
            $('.detailed').removeClass('detailed');
            if (!_self) $(this).toggleClass('detailed');
        }
    }).children("a").click(function(e){
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
        $.post(target.attr('data-url'), function(){
            target.fadeOut();
        });
        e.preventDefault();
    });

});
