$(document).ready(function(){

  var target = $('#target');
  $("<img/>").load(function () { //create in memory image, and bind the load event

    var nW = parseInt(document.getElementById("target").naturalWidth);
    var nH = parseInt(document.getElementById("target").naturalHeight);

    target.imgAreaSelect({
        aspectRatio: '1:1',
        handles: true,
        minHeight: 150,
        minWidth: 150,
        imageHeight: nH,
        imageWidth: nW,
        persistent: true,
        x1: 0, y1: 0, x2: 150, y2: 150,
        onSelectEnd: function (img, selection) {
                    $('#x').val(selection.x1);
                    $('#y').val(selection.y1);
                    $('#w').val(Math.abs(selection.x2 - selection.x1));
                    $('#h').val(Math.abs(selection.y2 - selection.y1));            
                }
    });

  }).attr("src", target.attr("src")); //set the src of the in memory copy after binding the load event, to avoid WebKit issues


});
