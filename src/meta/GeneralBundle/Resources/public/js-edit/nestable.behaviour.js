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
                .fail(function(xhr) {
                    process(xhr.responseJSON, "error", Translator.trans('alert.error.saving.changes'));
                });

            // Update parenting
            if (item.attr('data-name') !== undefined) {
                $.post(item.attr('data-url'), {
                    name: item.attr('data-name'),
                    value: item.parent().parent().attr('id') || 0
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

});