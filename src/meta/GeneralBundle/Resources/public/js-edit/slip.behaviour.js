/*global $,Translator,alertify,Slip */
/*jslint browser: true*/
$(document).ready(function() {

    var list = document.querySelector('ul.slip');

    if (list === null) {
        return;
    }

    var listObject = new Slip(list);

    list.addEventListener('slip:beforereorder', function(e) {
        // e.detail.insertBefore == null means we're at the end of the list, below the "new"
        if ($(e.target).hasClass('new')) {
            e.preventDefault();
        }
    });

    list.addEventListener('slip:reorder', function(e) {
        // e.target list item reordered.
        if (e.detail.insertBefore === null) {
            e.preventDefault();
        } else {

            var undo = e.target.nextSibling; // in case ...
            e.target.parentNode.insertBefore(e.target, e.detail.insertBefore);

            // Compute ranks
            var ul = $(e.target.parentNode),
                ranks = ul.find('li').map(function() {
                    return this.id;
                }).get().join();

            // Update rank
            $.post(ul.attr('data-url'), {
                ranks: ranks
            })
                .fail(function() {
                    // Undo
                    e.target.parentNode.insertBefore(e.target, undo);
                    alertify.error(Translator.trans('alert.error.saving.changes'));
                });

        }

    });

    list.addEventListener('slip:beforewait', function(e) {
        if (e.target.parentNode.className.indexOf('instant') > -1) {
            e.preventDefault();
        }
    }, false);

    list.addEventListener('slip:beforeswipe', function(e) {
        e.preventDefault();
    });

    // new item
    $("ul.slip > li > input")
        .on("keyup", function(e) {
            if (e.which === 13 && $(this).val() !== "") { // Trigger a save with the Return key for new item
                e.preventDefault();
                var parent = $(this).closest('ul'),
                    id = parent.attr('data-id'),
                    url = $(this).parent().attr("data-url"),
                    self = $(this),
                    dummy = $('<span>' + $(this).val() + '</span>');
                dummy.linkify();
                text = dummy.html();
                dummy.remove();
                $.post(url, {
                    text: text
                }, function(data) {
                    parent.children().last().before(data.item);
                    self.val('');
                    updateProgress(id);
                });
            }
        });

    // Calculate progress
    var updateProgress = function(id) {
        var val = $("ul.slip > li.done").length / ($("ul.slip > li").length - 1) * 100;
        if ($('.label-progress[data-list="' + id + '"]').length > 0) { // if there is a progress bar
            $('.label-progress[data-list="' + id + '"] > span').width(val + "%");
        }
        // If there is a sum up number
        if ($('.hint-progress[data-list="' + id + '"]').length > 0) { // if there is a progress bar
            $('.hint-progress[data-list="' + id + '"]').text(val.toFixed(0) + "%");
        }
    };

    // delete item
    $(document).on('click', "ul.slip > li > .actions > a.delete", function(e) {
        var li = $(this).closest('li'),
            id = $(this).parents('ul').attr('data-id'),
            url = $(this).attr("data-url");
        $.post(url, function(data) {
            li.animate({
                height: "toggle"
            }, 300, function() {
                $(this).remove();
                updateProgress(id);
            });
        });
    });

    // toggle item
    $(document).on('click', "ul.slip > li > .actions > a.toggle", function() {
        var li = $(this).closest('li'),
            id = $(this).parents('ul').attr('data-id'),
            url = $(this).attr("data-url");
        $.post(url, function(data) {
            li.replaceWith(data.item);
            updateProgress(id);
        });
    });

});