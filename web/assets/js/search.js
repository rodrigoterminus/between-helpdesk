var search = {
	init: function(){
		// $('.demo-content').masonry({
		//   itemSelector: '.mdl-cell'
		// });

		var listView = new infinity.ListView($('.demo-content'));
	},

	toggleSearch: function () {
		var $overlay = $("#search-overlay");
		var $button = $('#btn-search');
		var $fab = $('#fab');
		var $title = $('.mdl-layout__header').find('.mdl-layout-title');

		if ($title.attr('data-title') == undefined) {
			$title.attr('data-title', $title.text());
		}

		if ($overlay.css('display') == 'none') {
			$overlay.stop().slideDown('fast');
			$fab.fadeOut('fast');
			$button.find('.material-icons').text('close');
			$title.text('Pesquisar');
		}
		else {
			$overlay.stop().slideUp('fast');
			$fab.fadeIn('fast');
			$button.find('.material-icons').text('search');
			$title.text($title.data('title'));
		}
	},

	toggleSearchInScreen: function () {
		var $header = $('header.mdl-layout__header');
		var $headerRow = $header.find('.mdl-layout__header-row');
		// var $button = $('.mdl-layout__drawer-button');

		if ($header.hasClass('search-on-screen') == true) {
			$header.removeClass('search-on-screen');
			$headerRow.find('input').remove();
			$header.find('.back-button').remove();
			$header.find('.mdl-layout__drawer-button').show();
			$('.demo-content > .mdl-cell').show();
		}
		else {
			$header.addClass('search-on-screen');
			$input = $('<input/>')
				.prependTo($headerRow)
				.on('keyup', function(event) {
					if (event.keyCode == 27) {
						search.toggleSearchInScreen();
					}
					else {
						search.findInScreen($(this).val());
					}
				})
				.focus();

			var $backButton = $('<div/>')
				.addClass('mdl-layout__drawer-button back-button')
				.on('click.back', function() { search.toggleSearchInScreen(); })
				.html('<i class="material-icons">arrow_back</i>');
			
			$header.find('.mdl-layout__drawer-button')
				.after($backButton)
				.hide();
		}
	},

	findInScreen: function (term) {
		var $exceptions = $('.btw-card-criteria');
		var $items = $('.demo-content > .mdl-cell');

		$items.hide();
		$exceptions.show();

		$items.each(function(index, el) {
			var $item = $(this);
			var regex = new RegExp(term, 'ig');

			if ($.trim($item.find('.mdl-card__title-text:eq(0)').text()).match(regex) != null) {
				$item.show();
				return true;
			}

			var matched = false;

			$item.find('.fake-input').each(function(index, el) {
				var $item = $(this);

				if ($.trim($item.text()).match(regex) != null) {
					matched = true;
					return false;
				}				
			});

			if (matched == true) {
				$item.show();
			}			
		});
	}
};