var between = {
	init: function() {
		// Remove webkit form autofill
		// if ($.browser.webkit)
		    $('input').attr('autocomplete', 'false');
	},
	backButton: function(route) {
		setTimeout(function(){
			$menuButton = $('.mdl-layout__drawer-button');

			$backButton = $('<div/>')
				.addClass('mdl-layout__drawer-button')
				.on('click', function() { window.location = Routing.generate(route); })
				.html('<i class="material-icons">arrow_back</i>');

			$menuButton
				.after($backButton)
				.remove();
		}, 500);
	},

	formSubmit: function(name) {
		$('form[name="'+ name +'"]').find('*[type="submit"]').trigger('click');
	}
};