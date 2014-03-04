/*global $,Translator,alertify */
/*jslint browser: true*/
$(document).ready(function() {

    // Post in AJAX
    $(".comment form").on('submit', function(e) {
        e.preventDefault();

        var _self = $(this),
            firstTimelineItem = _self.parent('.comment').siblings('.timeline').children().first(),
            comment = $.trim(_self.children('textarea').val());

        if (comment === "") {
            _self.children('textarea').blur().val("");
            return false;
        }

        _self.find('[type=submit]').attr('disabled', 'disabled');
        _self.find('.working i').show();

        $.post(_self.attr('action'), {
            'comment': comment
        })
            .done(function(data) {
                process(data, "success", Translator.trans('comment.added'));

                // Empties in case of success
                _self.children('textarea').blur().val("");

                // Adds the comment, depends on the timeline type
                if (firstTimelineItem.length == 0) {
                    timeline.append(data.comment);
                } else if (firstTimelineItem.hasClass("step") && firstTimelineItem.attr('current')) {
                    firstTimelineItem.after(data.comment);
                } else {
                    firstTimelineItem.before(data.comment);
                }
            })
            .fail(function(xhr) {
                process(xhr.responseJSON, "error", Translator.trans('comment.cannot.add'));
            })
            .always(function() {
                _self.find('[type=submit]').removeAttr('disabled');
                _self.find('.working i').fadeOut();
            });
    });

    // Validates in AJAX
    $(document).on('click', '.validate-trigger', function(e) {
        e.preventDefault();
        var countBox = $(this).siblings('span'),
            validationBox = $(this).closest('.validation');

        $.post($(this).attr('data-url'))
            .done(function(data) {
                countBox.html(data);
                validationBox.toggleClass('validated');
                alertify.success(Translator.trans('comment.validated'));
            })
            .fail(function(xhr) {
                alertify.error(Translator.trans('comment.cannot.validate'));
            });

    });

    // Deletes in AJAX
    $(document).on('click', '.delete-trigger', function(e) {
        e.preventDefault();
        var actionBox = $(this).closest('.actions'),
            commentBox = actionBox.siblings('.text');

        $.post($(this).attr('data-url'))
            .done(function() {
                commentBox.html('<em>' + Translator.trans('comment.deleted') + '</em>');
                actionBox.fadeOut();
                alertify.success(Translator.trans('comment.been.deleted'));
            })
            .fail(function(xhr) {
                alertify.error(Translator.trans('comment.cannot.delete'));
            });

    });

});