/*global $,Translator,alertify,Slip */
/*jslint browser: true*/
$(document).ready(function() {

    var list = document.querySelector('ul.slip'),
        listObject = new Slip(list);

    list.addEventListener('slip:reorder', function(e) {
        // e.target list item reordered.
        // e.detail.insertBefore == null means we're at the end of the list, below the "new"
        if ($(e.target).hasClass('new') || e.detail.insertBefore === null) {
            e.preventDefault();
        } else {
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
                .success(function() {
                    alertify.success(Translator.trans('alert.changes.saved'));
                })
                .error(function() {
                    alertify.error(Translator.trans('alert.error.saving.changes'));
                });
        }

    });

    list.addEventListener('slip:beforewait', function(e) {
        if (e.target.parentNode.className.indexOf('instant') > -1) {
            e.preventDefault();
        }
    }, false);

    list.addEventListener('slip:afterswipe', function(e) {
        // e.target list item swiped
        e.target.parentNode.removeChild(e.target);
        $.post($(e.target).attr('data-delete'));
    });

    // new item
    $("ul.slip > li > input")
        .on("keyup", function(e) {
            if (e.which === '13') { // Trigger a save with the Return key for new item
                e.preventDefault();
                var parent = $(this).closest('ul'),
                    text = $(this).val(),
                    url = $(this).parent().attr("data-url"),
                    self = $(this);
                $.post(url, {
                    text: text
                }, function(data) {
                    parent.children().last().before(data);
                    self.val('');
                });
            }
        });

    // toggle item
    $(document).on('click', "ul.slip > li > .actions > a", function() {
        var li = $(this).closest('li'),
            url = $(this).attr("data-url");
        $.post(url, function(data) {
            li.replaceWith(data);
            var val = $("ul.slip > li.done").length / ($("ul.slip > li").length - 1) * 100;
            $('.label-progress > span').width(val + "%");
        });
    });

});