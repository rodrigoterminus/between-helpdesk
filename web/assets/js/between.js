var between = {
    messages: [],
    init: function () {
        // Remove webkit form autofill
        // if ($.browser.webkit)
        $('input').attr('autocomplete', 'false');
        
        $(document).ready(function() {
            between.loading.hide();
        });
        
        $(window).on('beforeunload', function() {
            between.loading.show();
        });
        
        $(document).on('submit', 'form', function() {
            between.loading.show();
        });
        
        $(document).on('click', '.mdl-dialog .close', function() {
            $(this).closest('.mdl-dialog').get(0).close();
        });
        
        // Open/Close menu
        if ($(window).width() <= 1024 && between.isTouchDevice()) {
            $('body').swipe({
                swipeRight: function() {
                    var $menu = $('.mdl-layout__drawer');

                    if (!$menu.hasClass('is-visible') && $(window).width() <= 1024) {
                        $('.mdl-layout__drawer-button:not(".back-button")').trigger('click');  
                    }
                },
                swipeLeft: function() {
                    var $menu = $('.mdl-layout__drawer');

                    if ($menu.hasClass('is-visible') && $(window).width() <= 1024) {
                        $('.mdl-layout__obfuscator').trigger('click'); 
                    }
                }
            });
        }
        
        ion.sound({
            sounds: [{
                name: "button_tiny"
            }],
            volume: 1,
            path: 'http://'+ window.location.host +'/assets/vendor/ion-sound/sounds/',
            preload: true
        });
                
        notifier.init();
    },
    loading: {
        show: function() {
            $('.loading-overlay').show();
        },
        hide: function() {
            $('.loading-overlay').hide();
        }
    },
    isTouchDevice: function() {
        return 'ontouchstart' in window        // works on most browsers 
            || navigator.maxTouchPoints;       // works on IE10/11 and Surface
    },
    backButton: function (route) {
        $(document).ready(function () {
            var setButton = setInterval(function () {
                var $menuButton = $('.mdl-layout__drawer-button');

                if ($menuButton.length > 0) {
                    var $backButton = $('<div/>')
                        .addClass('mdl-layout__drawer-button back-button')
                        .on('click', function () {
                            between.loading.show();
                            
                            if (document.referrer === window.location.href) {
                                window.location = Routing.generate(route);
                            }
                            else {
                                window.history.back();
                            }
                        })
                        .html('<i class="material-icons">arrow_back</i>');

                    $menuButton
                        .after($backButton)
                        .hide();

                    clearInterval(setButton);
                }
            }, 500);
        });
    },
    showConfirmationDialog: function(options) {
        const $dialog = _dialog.create(
            options.title ? _dialog.title(options.title) : null,
            options.message ? _dialog.content(options.message) : null,
            _dialog.actions([
                {
                    cssClass: 'close',
                    label: 'Cancelar',
                    onClick: options.onClose ? options.onClose : null,
                },
                {
                    cssClass: 'mdl-button--colored',
                    label: 'Confirmar',
                    onClick: options.onConfirm ? options.onConfirm : null,
                },
            ])
        );

        $('#dialogs').append($dialog);
        $dialog.attr('id', 'dialog-confirm');
        $dialog.get(0).showModal()
    },
    submitForm: function (name) {
        $('form[name="' + name + '"]').find('*[type="submit"]').trigger('click');
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
                        $selectbox.append('<option value="' + response[i].id + '">' + response[i].text + '</option>');
                    }
                    ;
                } else
                    $selectbox.html('<option value=""></option>');
            },
            error: function () {
                $selectbox.html('<option value=""></option>');
            },
            complete: function () {
                $selectbox.removeAttr('disabled');
            }
        });
    },
    stopwatch: function() {
        setInterval(function datetimeUpdate() {
            $('.stopwatch[data-datetime]').each(function() {
                var datetime = $(this).data('datetime');
                
                if ((datetime + '').indexOf('/') === -1 && (datetime + '').indexOf('-') === -1) {
                    datetime = parseInt(datetime) * 1000;
                }
                
                if (datetime !== '') {
                    $(this).text(moment(datetime).from(moment()));
                }
            });
            
            return datetimeUpdate;
        }(), 60000);
    }
};

moment.locale('pt-br');

var dialog = null;

$.fn.hasExtension = function(exts) {
    return (new RegExp('(' + exts.join('|').replace(/\./g, '\\.') + ')$')).test($(this).val());
}