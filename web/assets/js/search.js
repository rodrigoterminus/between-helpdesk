var search = {
    init: function () {
        $('.btn-export').on('click', function() {
            exporter.get($(this).data('format'));
        });
        
        $('#form-search').on('submit', function (event) {
            var $submit = $(this).find('button[type="submit"]');

            $submit
                .attr('disabled', 'disabled')
                .text('Pesquisando...');
        });
    },
    toggleSearch: function () {
        const $overlay = $("#search-overlay");
        const $button = $('#btn-search');
        const $fab = $('#fab');
        const $title = $('.mdl-layout__header').find('.mdl-layout-title');
        const $headerActions = $('#header-actions')

        if (!$title.attr('data-title')) {
            $title.attr('data-title', $title.text());
        }

        if ($overlay.css('display') === 'none') {
            $overlay.stop().slideDown('fast');
            $fab.fadeOut('fast');
            $button.find('.material-icons').text('check');
            $title.text('Filtrar');
            $headerActions.find('.mdl-button:not(#btn-search):visible').attr('data-search', 'hidden')
        } else {
            $overlay.stop().slideUp('fast');
            $fab.fadeIn('fast');
            $button.find('.material-icons').text('filter_alt');
            $title.text($title.data('title'));
            $headerActions.find('.mdl-button[data-search]').removeAttr('data-search')
            $('#form-search').submit()
        }
    },
    toggleSearchOnScreen: function () {
        var $header = $('header.mdl-layout__header');
        var $headerRow = $header.find('.mdl-layout__header-row');

        if ($header.hasClass('search-on-screen')) {
            $header.removeClass('search-on-screen');
            $headerRow.find('input').remove();
            $header.find('.back-button').remove();
            $header.find('.mdl-layout__drawer-button').show();
            $('.demo-content > .mdl-cell').show();
        } else {
            $header.addClass('search-on-screen');
            $input = $('<input/>')
                .prependTo($headerRow)
                .on('keyup', function (event) {
                    if (event.keyCode == 27) {
                        search.toggleSearchOnScreen();
                    } else {
                        search.findOnScreen($(this).val());
                    }
                })
                .focus();

            var $backButton = $('<div/>')
                .addClass('mdl-layout__drawer-button back-button')
                .on('click.back', function () {
                    search.toggleSearchOnScreen();
                })
                .html('<i class="material-icons">arrow_back</i>');

            $header.find('.mdl-layout__drawer-button')
                .after($backButton)
                .hide();
        }
    },
    findOnScreen: function (term) {
        var $exceptions = $('.btw-card-criteria');
        var $items = $('.demo-content > .mdl-cell');

        $items.hide();
        $exceptions.show();

        $items.each(function (index, el) {
            var $item = $(this);
            var regex = new RegExp(term, 'ig');

            if ($.trim($item.find('.mdl-card__title-text:eq(0)').text()).match(regex) != null) {
                $item.show();
                return true;
            }

            var matched = false;

            $item.find('.fake-input').each(function (index, el) {
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