$(document).ready(function(){

  $("#target").load(function(){

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

  });

  function showCoords(c)
  {
    $('#x').val(c.x);
    $('#y').val(c.y);
    $('#w').val(c.w);
    $('#h').val(c.h);
  };

});
