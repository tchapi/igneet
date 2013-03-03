$(document).ready(function(){
   
   // Personal Dashboard graphs

   var createWeeklyGraph = function(node, width, height, dataset) {

      //Width and height
      var margin = 20;

      var w = width - margin;
      var h = height;

      var barw = w/8;

      // Date
      var x = d3.time.scale()
         .domain([d3.time.day.ceil(new Date()), d3.time.day.offset(d3.time.day.ceil(new Date()), -8)])
         .range([w, 0]);

      var y = d3.scale.linear()
         .domain([0, d3.max(dataset, function(d) { 
            return parseInt(d.nb_actions?d.nb_actions:0) + parseInt(d.nb_comments?d.nb_comments:0); 
         })])
         .range([0, h]);

      // Colors
      var actions_hue = 200;
      var comments_hue = 240;

      // Create SVG element
      var svg = d3.select(node)
            .append("svg:svg")
            .attr("width", w)
            .attr("height", h + margin*2)
            .append("g")
            .attr("transform", "translate(0," + margin + ")");

      if (dataset[0].nb_actions) {
         svg.selectAll("g")
            .data(dataset)
            .enter()
            .append("rect")
            .attr("x", function(d ) {
               return x(d3.time.day.floor(new Date(d.date)));
            })
            .attr("y", function(d) {
               return h - y(d.nb_actions);
            }) 
            .attr("width", barw)
            .attr("height", function(d) {
               return y(d.nb_actions);
            })
            .attr('fill', function(d) {
               return d3.hsl(actions_hue, 0.5, 0.9 - (y(d.nb_actions)/h/2) );
            });
      }
      if (dataset[0].nb_comments) {
         svg.selectAll("g")
            .data(dataset)
            .enter()
            .append("rect")
            .attr("x", function(d ) {
               return x(d3.time.day.floor(new Date(d.date)));
            })
            .attr("y", function(d) {
               return h - y(d.nb_comments) - y((d.nb_actions==null)?0:d.nb_actions);
            }) 
            .attr("width", barw)
            .attr("height", function(d) {
               return y(d.nb_comments);
            })
            .attr('fill', function(d) {
               return d3.hsl(comments_hue, 0.5, 0.9 - (y(d.nb_comments)/h/2) );
            });
      }

      // Put labels on top
      svg.selectAll("text")
         .data(dataset)
         .enter().append("text")
         .attr('class', 'top-label')
         .text(function(d) {
             return parseInt(d.nb_actions?d.nb_actions:0) + parseInt(d.nb_comments?d.nb_comments:0);
         })
         .attr("x", function(d ) {
            return x(new Date(d.date));
         })
         .attr("y", function(d) {
             return h - ( y((d.nb_actions==null)?0:d.nb_actions) + y((d.nb_comments==null)?0:d.nb_comments) );
         })
         .attr("dx", barw/2)
         .attr("dy", -2)
         .attr("text-anchor", "middle")
         .attr('fill', "#AAA");


      // Axis
      var xAxis = d3.svg.axis()
          .scale(x)
          .orient('bottom')
          .ticks(d3.time.days, 1)
          .tickFormat(d3.time.format('%a %d'))
          .tickPadding(8);

      svg.append('g')
          .attr('class', 'axis')
          .attr('transform', 'translate(0, ' + h + ')')
          .call(xAxis);

      // Pad the text a little
      svg.selectAll(".axis text") // select all the text elements for the xaxis
         .attr("transform", "translate(" + barw/2 + ",-5)");

   };

   $('.graph').each(function(){

      createWeeklyGraph(
         this,
         parseInt($(this).attr('graph-width')),
         parseInt($(this).attr('graph-height')),
         $.parseJSON($(this).attr('graph-data'))
      );

   });

});