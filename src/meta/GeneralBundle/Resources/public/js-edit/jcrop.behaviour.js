$(document).ready(function(){

  $('#target').Jcrop({
      onChange: showCoords,
      onSelect: showCoords,
      boxWidth: 400,
      boxHeight: 400,
      trueSize: [document.getElementById('target').naturalWidth, document.getElementById('target').naturalHeight],
      minSize: [150, 150],
      bgColor:     'black',
      bgOpacity:   .4,
      setSelect:   [ 0, 0, 150, 150 ],
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