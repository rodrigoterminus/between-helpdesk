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
	},

	loadSelectbox: function ($selectbox, route, data) {
		$selectbox
			.attr('disabled', 'disabled')
			.find('option')
			.remove();

		$selectbox.html('<option value="">Carregando...</option>')

		$.ajax({
			url: Routing.generate(route),
			type: 'get',
			data: data,
			success: function (response) {
				if (response.length > 0) {
					$selectbox.html('<option value="">Selecione</option>')

					for (var i = response.length - 1; i >= 0; i--) {
						$selectbox.append('<option value="'+ response[i].id +'">'+ response[i].text +'</option>');
					};
				}
				else
					$selectbox.html('<option value=""></option>');
			},
			error: function () {
				$selectbox.html('<option value=""></option>');	
			},
			complete: function () {
				$selectbox.removeAttr('disabled');
			}
		});
	}
};