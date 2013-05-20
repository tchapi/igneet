jQuery(document).ready(function() {
	
	//if submit button is clicked
	jQuery('.contact-form #submit').click(function () {		
		
		//Get the data from all the fields
		var name = jQuery('input[name=name]');
		var email = jQuery('input[name=email]');
		var message = jQuery('textarea[name=message]');

		//Simple validation to make sure user entered something
		//If error found, add highlight class to the text field
		if (name.val()=='') {
			name.addClass('highlight');
			return false;
		} else name.removeClass('highlight');
		
		if (email.val()=='') {
			email.addClass('highlight');
			return false;
		} else email.removeClass('highlight');
		
		if (message.val()=='') {
			message.addClass('highlight');
			return false;
		} else message.removeClass('highlight');
		
		//disabled all the text fields
		jQuery('.text').attr('disabled','true');
		
		//show the loading sign
		$(this).attr('value', 'Envoi en cours ...');

		jQuery.post(
			jQuery('.contact-form form').attr('action'), 
			{ name : name.val(), email : email.val(), message : message.val() },
			function(data) {
						
				if (data == 1) {
					jQuery('.done').fadeIn('slow');
					jQuery('.contact-form form').fadeOut('slow');
				} else {
					jQuery('.error').fadeIn('slow');
					jQuery('.contact-form form').fadeOut('slow');
				}
				
			}
		);
					
		return false;
						
		//cancel the submit button default behaviours

	});

});	
