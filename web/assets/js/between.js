var between = {
	messages: [],

	init: function() {
		// Remove webkit form autofill
		// if ($.browser.webkit)
		    $('input').attr('autocomplete', 'false');

		between.showFlashMessages();
	},

	backButton: function(route) {
		$(document).ready(function () {
			var setButton = setInterval(function() {
				var $menuButton = $('.mdl-layout__drawer-button');

				if ($menuButton.length > 0) {
					var $backButton = $('<div/>')
						.addClass('mdl-layout__drawer-button back-button')
						.on('click', function() { window.location = Routing.generate(route); })
						.html('<i class="material-icons">arrow_back</i>');
					
					$menuButton
						.after($backButton)
						.remove();

					clearInterval(setButton);
				}				
			}, 500);
		});
	},

	showFlashMessages: function () {
		var interval = setInterval(function () {
			var notification = document.querySelector('.mdl-js-snackbar');
			var show = function () {
        notification.MaterialSnackbar.showSnackbar(between.messages[i - 1]);
      };

			if (notification.MaterialSnackbar != undefined) {
		    var timeout = 2750;
		    var timeSum = 0;

		    for (var i=0; i < between.messages.length; i++) {
		      var timeout = timeout + 250;
		      var index = i;

		      setTimeout(show.bind(index), timeSum);

		      timeSum += timeout;
		    } 

		    clearInterval(interval);
			}
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