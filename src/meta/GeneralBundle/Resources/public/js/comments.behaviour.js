/*global $,Translator,alertify */
/*jslint browser: true*/
$(document).ready(function() {

    // Validates in AJAX
    $('.validate-trigger').click(function() {

        var countBox = $(this).siblings('span'),
            validationBox = $(this).closest('.validation');

        $.post($(this).attr('data-url'))
            .success(function(data) {
                countBox.html(data);
                validationBox.toggleClass('validated');
                alertify.success(Translator.trans('comment.validated'));
            })
            .error(function() {
                alertify.error(Translator.trans('comment.cannot.validate'));
            });

    });

    // Deletes in AJAX
    $('.delete-trigger').click(function() {

        var actionBox = $(this).closest('.actions'),
            commentBox = actionBox.siblings('.text');

        $.post($(this).attr('data-url'))
            .success(function() {
                commentBox.html('<em>' + Translator.trans('comment.deleted') + '</em>');
                actionBox.fadeOut();
                alertify.success(Translator.trans('comment.been.deleted'));
            })
            .error(function() {
                alertify.error(Translator.trans('comment.cannot.delete'));
            });

    });

});