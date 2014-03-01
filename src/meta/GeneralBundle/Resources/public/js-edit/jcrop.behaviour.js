/*global $,Translator,alertify */
/*jslint browser: true*/
$(document).ready(function() {

    var nW = parseInt(document.getElementById("target").naturalWidth, 10),
        nH = parseInt(document.getElementById("target").naturalHeight, 10),
        min = Math.min(300 + 1, nW, nH) - 1;

    var cW = $('.content-full').width();

    var showCoords = function(c) {
        $('#x').val(c.x);
        $('#y').val(c.y);
        $('#w').val(Math.abs(c.x2 - c.x));
        $('#h').val(Math.abs(c.y2 - c.y));
    }

    var jcrop_api;

    $('#target').Jcrop({
        allowSelect: false,
        boxWidth: cW,
        boxHeight: cW,
        aspectRatio: 1,
        minSize: [min, min],
        setSelect: [0, 0, min, min],
        onSelect: showCoords,
        onChange: showCoords
    }, function() {
        $("#loading").hide();
        jcrop_api = this;
        jcrop_api.setSelect([0, 0, min, min]);
    });

});