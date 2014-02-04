/*global $,Translator,alertify */
/*jslint browser: true*/
$(document).ready(function() {

    var target = $('#target');
    $("<img/>").load(function() { //create in memory image, and bind the load event

        var nW = parseInt(document.getElementById("target").naturalWidth, 10),
            nH = parseInt(document.getElementById("target").naturalHeight, 10),
            min = Math.min(150 + 1, nW, nH) - 1;

        target.imgAreaSelect({
            aspectRatio: '1:1',
            handles: true,
            minHeight: min,
            minWidth: min,
            persistent: true,
            imageHeight: nH,
            imageWidth: nW,
            x1: 0,
            y1: 0,
            x2: min,
            y2: min,
            onSelectEnd: function(img, selection) {
                $('#x').val(selection.x1);
                $('#y').val(selection.y1);
                $('#w').val(Math.abs(selection.x2 - selection.x1));
                $('#h').val(Math.abs(selection.y2 - selection.y1));
            }
        });

    }).attr("src", target.attr("src")); //set the src of the in memory copy after binding the load event, to avoid WebKit issues

});