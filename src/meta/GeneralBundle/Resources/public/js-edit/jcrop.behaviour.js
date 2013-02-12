$(document).ready(function(){

  var target = $('#target');
  $("<img/>").load(function () { //create in memory image, and bind the load event

    var nW = parseInt(document.getElementById("target").naturalWidth);
    var nH = parseInt(document.getElementById("target").naturalHeight);

    $('#target').Jcrop({
        onChange: showCoords,
        onSelect: showCoords,
        boxWidth: 400,
        boxHeight: 400,
        trueSize: [nW, nH],
        minSize: [150, 150],
        bgColor:     'black',
        bgOpacity:   .4,
        setSelect:   [ 0, 0, 150, 150 ],
        aspectRatio: 1
    });

  }).attr("src", target.attr("src")); //set the src of the in memory copy after binding the load event, to avoid WebKit issues

  function showCoords(c)
  {
    $('#x').val(c.x);
    $('#y').val(c.y);
    $('#w').val(c.w);
    $('#h').val(c.h);
  };

});
