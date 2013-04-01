$(document).ready(function(){
  
  // Skills
  $("#user_skills.select2-trigger").select2({
    placeholder: "Select your skills",
    width: "542px"
  });

  // Creating a new idea > creators
  $("#idea_creators.select2-trigger").select2({
    placeholder: "Indicate the creators",
    width: "542px"
  });

  // Creating a new project > skills
  $("#standardProject_neededSkills.select2-trigger").select2({
    placeholder: "Indicate needed skills",
    width: "542px"
  });

  // Timeline filters for ideas and projects
  $("#timeline_filters.select2-trigger").select2({
    placeholder: "Optional timeline filters",
    width: "812px"
  });
  $("#timeline_reset").click(function(){
    $("#timeline_filters.select2-trigger").select2("val",[]).trigger("change");
  });
  $("#timeline_filters.select2-trigger").on("change", function(e) {
    if (typeof e.val === "undefined" || e.val.length == 0){

      $('li.logWrapper').show();
      $('li.dateWrapper').show();

    } else {

      $('li.logWrapper').hide();
      $('li.dateWrapper.date').hide();
      for(group in e.val){
        $('li.logWrapper[' + e.val[group] + ']').show();
      }
      $('li.dateWrapper.date').each(function(){
        if ( $(this).nextAll(':visible:first').length !== 0 && !$(this).nextAll(':visible:first').hasClass('pass') ) {
          $(this).show();
        }
      });
    }

  });

});