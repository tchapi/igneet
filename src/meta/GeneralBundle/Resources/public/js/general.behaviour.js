/*global $,Translator,alertify */
/*jslint browser: true*/
$(document).ready(function() {

    /*
     * Responsive slide menu
     */
    var $menu_trigger = $(".menu-trigger");
    if ($menu_trigger !== undefined) {
        $menu_trigger.on('click', function() {
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
    $("[dismiss]").on('click', function() {
        var targetId = $(this).attr('dismiss');
        $("#" + targetId).slideUp();
        document.cookie = "igneet_dismiss[" + targetId + "]=true; path=/; expires=Wed, 1 Jan 2020 00:42:42 UTC;";
    });
    $("[dismiss-reset=all]").on('click', function() {
        document.cookie = "igneet_dismiss[shared_projects]=false; expires=; path=/;";
        // ** Others ? **
    });

    /*
     * Mark notifications as read
     */
    $("#markRead").click(function() {
        $.post($(this).attr('data-url'))
            .success(function(data) {
                if (data.redirect) {
                    window.location.replace(data.redirect);
                }
            })
            .error(function() {
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

});