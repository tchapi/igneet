$(document).ready(function(){

  $('#target').Jcrop({
      onChange: showCoords,
      onSelect: showCoords,
      boxWidth: 400,
      boxHeight: 400,
      minSize: [100, 100],
      bgColor:     'black',
      bgOpacity:   .4,
      setSelect:   [ 0, 0, 100, 100 ],
      aspectRatio: 1
  });

  function showCoords(c)
  {
    $('#x').val(c.x);
    $('#y').val(c.y);
    $('#w').val(c.w);
    $('#h').val(c.h);
  };


});