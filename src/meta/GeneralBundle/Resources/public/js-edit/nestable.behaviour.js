/*global $,Translator,alertify,process */
/*jslint browser: true*/
$(document).ready(function() {

    var sortableList = $(".tree.dd"),

        // Callback for ranks and nesting
        updateRanksAndNesting = function(l, e) {

            var success = false,
                list = l.length ? l : $(l.target),
                item = e.length ? e : $(e.target),
                ranks = list.find('li').map(function() {
                    return this.id;
                }).get().join();

            // Update rank
            $.post(list.children('ul').attr('data-url'), {
                ranks: ranks
            })
                .done(function(data) {
                    process(data, "success", Translator.trans('alert.changes.saved'));
                })
                .fail(function(xhr) {
                    process(xhr.responseJSON, "error", Translator.trans('alert.error.saving.changes'));
                });

            // Update parenting
            if (item.attr('data-name') !== undefined) {
                $.post(item.attr('data-url'), {
                    name: item.attr('data-name'),
                    value: item.parent().parent().attr('id') || 0
                })
                    .done(function(data) {
                        process(data, "success", Translator.trans('alert.changes.saved'));
                    })
                    .fail(function(xhr) {
                        process(xhr.responseJSON, "error", Translator.trans('alert.error.saving.changes'));
                    });
            }

        };

    // Triggers nestable()
    sortableList.nestable({
        listNodeName: 'ul',
        expandBtnHTML: '<a data-action="expand"><i class="fa fa-caret-right"></i></a>',
        collapseBtnHTML: '<a data-action="collapse"><i class="fa fa-caret-down"></i></a>',
        callback: updateRanksAndNesting
    });

    // Toggle index (pages) view
    $(".tree > .toggle").click(function(e) {
        $(this).parent().toggleClass("open");
        e.preventDefault();
        document.cookie = "igneet_trees_open=" + ($(this).parent().hasClass('open') ? "true" : "false") + "; path=/; expires=Wed, 1 Jan 2020 00:42:42 UTC;";
    });

    // Add new item in the list
    $(".new").click(function(e) {

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

});