var ticket = {
    id: null,
    number: null,
    rating: {
        solved: null,
        rate: null,
        comment: null
    },
    init: function () {
        $(document).ready(function () {
            $('#appbundle_ticket_customer').on('change', function () {
                between.loadSelectbox($('#appbundle_ticket_project'), 'project_json', {customer: $(this).val()});
            });
        });
        
        $('#modal-transfer-ticket').find('.close').on('click', function() {
            var dialog = document.querySelector('dialog');
            dialog.close();
        });
        
        $('#btn-transfer-ticket').on('click', function () {
           ticket.transfer($('#transfer-user').val()); 
        });
        
        $('#appbundle_ticket').on('submit', function(e) {
            if (!$('input[type=file]').hasExtension(['.jpg', '.jpeg', '.bmp', '.png', '.gif'])) {
                alert('Apenas arquivos de imagem podem ser enviados.');
                return false;
            }
        });
        
        // Comment
        $(document).on('click', '#btn-send-comment', function() {
            comment.add()
                .done(function() {
                    var $comments = $('#comments');
                    var $badge = $comments.closest('.mdl-card').find('.mdl-badge');
                    var counter = parseInt($badge.data('badge'));
                    
                    if (!$comments.is(':visible')) {
                        ticket.toggleComments();
                    }
                   
                    $badge.attr('data-badge', counter + 1);
                });
        });
        $('#btn-toggle-comments').on('click', ticket.toggleComments);
        
        // Rating
        $('#btn-open-rating').on('click', function() {
            dialog = document.getElementById('dialog-rating');
        
            if (! dialog.showModal) {
              dialogPolyfill.registerDialog(dialog);
            }

            dialog.showModal();
        });
        
        if (window.location.search === '?rating=true') {
            $('#btn-open-rating').trigger('click');
        }
        
        $('#dialog-rating .rating-thumbs')
            .on('click', function() {
                var $thumbs = $('#dialog-rating .rating-thumbs');
                
                ticket.rating.solved = Boolean($(this).data('value'));
                
                $thumbs.removeClass('choosen');
                $(this).addClass('choosen');
            });
        
        $('#dialog-rating .rating-star')
            .on('click', function() {
                ticket.rating.rate = parseInt($(this).data('rate'));
            
                $('.rating-star').removeClass('fulfilled');
            
                for (var i = 1; i <= ticket.rating.rate; i++) {
                    var $thumb = $('#rating-star-'+ i);
                    
                    $thumb.addClass('fulfilled')
                    $thumb.find('i').text('star');
                }
            })
            .on('mouseover', function() {
//                $('.rating-star').find('i').text('star_border');
                $('.rating-star').removeClass('fulfilled');
                
                for (var i = 1; i <= parseInt($(this).data('rate')); i++) {
                    var $thumb = $('#rating-star-'+ i);
                    
                    $thumb.addClass('fulfilled')
                    $thumb.find('i').text('star');
                }
            })
            .on('mouseleave', function() {
                var $thumbs = $('.rating-star');
            
                $thumbs.removeClass('fulfilled');
//                $thumbs.find('i').text('star_border');
            
                if (ticket.rating.rate !== null) {
                    for (var i = 1; i <= ticket.rating.rate; i++) {
                        var $thumb = $('#rating-star-'+ i);
                    
                        $thumb.addClass('fulfilled')
                        $thumb.find('i').text('star');
                    }
                }
            });
    },
    finish: function () {
        if (confirm('Confirma o fechamento deste ticket?')) {
            window.location = window.location.href + '/finish';
        }
    },
    reopen: function () {
        if (confirm('Confirma a reabertura deste ticket?')) {
            window.location = window.location.href + '/reopen';
        }
    },
    openTransferDialog: function () {
        dialog = document.getElementById('dialog-transfer');
        
        if (! dialog.showModal) {
          dialogPolyfill.registerDialog(dialog);
        }
        
        dialog.showModal();
    },
    openCommentDialog: function () {
        dialog = document.getElementById('dialog-new-comment');
        
        if (! dialog.showModal) {
          dialogPolyfill.registerDialog(dialog);
        }
        
        dialog.showModal();
    },
    transfer: function (userId) {
        if (userId === '' || userId === undefined) {
            alert('Selecione um atendente para transferir o ticket');
            return false;
        }
        else {
            window.location = window.location.href + '/transfer/'+ userId;
        }
    },
    watcher: function () {
        $.get(Routing.generate('ticket_watcher', { number: ticket.number }))
            .done(function(response) {
                var $button = $('#btn-watcher');
                var $watchers = $('#ticket-watchers');
                var watcher = {};
                
                if (response.subscribed === true) {
                    watcher.icon = 'visibility_off';
                    watcher.label = 'Deixar de acompanhar';
                    $watchers.append('<span class="ticket-watcher" data-id="'+ response.user.id +'">'+ response.user.name +'</span>');
                    notifier.notify({message: 'Você está assistindo este ticket.'}, true);
                }
                else {
                    watcher.icon = 'visibility';
                    watcher.label = 'Acompanhar este ticket';
                    $watchers.find('.ticket-watcher[data-id='+ response.user.id +']').remove();
                    notifier.notify({message: 'Você deixou de assistir este ticket.'}, true);
                }
                
                if ($watchers.find('.ticket-watcher').length === 0 && $watchers.is(':visible')) {
                    ticket.toggleWatchers();
                }
                else if ($watchers.find('.ticket-watcher').length > 0 && !$watchers.is(':visible')) {
                    ticket.toggleWatchers();
                }
                
                $button.find('i').text(watcher.icon);
                $('#floating-actions').find('.mdl-tooltip[for="btn-watcher"]').text(watcher.label);
            })
            .fail(function() {
                notifier.notify({message: 'Não foi possível te inscrever neste ticket.'}, true);
            });
    },
    toggleWatchers: function() {
        var $watchers = $('#ticket-watchers');
        
        if ($watchers.is(':visible')) {
            $watchers.hide();
        }
        else {
            $watchers.show();
        }
    },
    toggleComments: function() {
        var $comments = $('#comments');
        var $btn = $('#btn-toggle-comments');
        var $tooltip = $('.mdl-tooltip[for=btn-toggle-comments]');
        
        if ($comments.is(':visible')) {
            $comments.hide();
            $btn.find('.material-icons').text('expand_more');
            $tooltip.text('Exibir comentários');
        }
        else {
            $comments.show();
            $btn.find('.material-icons').text('expand_less');
            $tooltip.text('Esconder comentários');
        }
    },
    submitRating: function() {
        if (ticket.rating.solved === null) {
            alert('Por favor, informe se a solicitação foi atendida.');
            return false;
        }
        else if (ticket.rating.rate === null) {
            alert('Por favor, dê uma nota para este atendimento.');
            return false;
        }
        else {
            ticket.rating.comment = $.trim($('#rating-comment').val());
            
            $('#dialog-rating .mdl-dialog__actions .mdl-button').attr('disabled', 'disabled');
            $('#dialog-rating .mdl-dialog__actions .mdl-button:last-child').text('Enviando');
            
            $.post(Routing.generate('ticket_rating', { id: ticket.id }), ticket.rating)
                .done(function() {
                    $('#rating-container').remove();
                    dialog.close();
                    notifier.notify({message: 'Muito obrigado por avaliar! :)'}, true);
                })
                .fail(function() {
                    notifier.notify({message: 'Erro ao enviar sua avaliação.'}, true);
                })
                .always(function() {
                    $('#dialog-rating .mdl-dialog__actions .mdl-button:last-child').text('Enviar');
                    $('#dialog-rating .mdl-dialog__actions .mdl-button').removeAttr('disabled');
                });
                
            return true;
        }
    },
    showCardsExtraInfo: function() {
        var $container = $('<div/>')
            .addClass('card-footer mdl-card__supporting-text mdl-card--border mdl-color-text--white');
        
        search.result.forEach(function(ticket) {
            var $card = $('#search-card-'+ ticket.id);
            
            if (ticket.status === 'created' || ticket.status === 'running') {
                var time = moment(ticket.createdAt * 1000).from(moment());
                var $bar = $container.clone()
                    .append('<i class="material-icons" style="margin-right: 8px;">schedule</i>')
                    .append('<span class="stopwatch" data-datetime="'+ ticket.createdAt +'">'+ time +'</div>')
                    .removeClass('mdl-color-text--white')
                    .addClass('vertical-align-middle')
                    .appendTo($card);
                
                if (ticket.status === 'created') {
                    $card.addClass('mdl-color--yellow-100');
                    $card.find('.mdl-button')
                        .removeClass('mdl-color-text--amber-900')
                        .addClass('mdl-color-text--yellow-600')
                    $bar.addClass('mdl-color--yellow-200');
                }
                else {
                    $bar.addClass(' mdl-color--grey-200');
                }
                
//                $bar.text(time);
            }
            else if (ticket.rate !== null) {
                var color = (ticket.solved) ? 'green' : 'red';                
                var thumb = (ticket.solved) ? 'thumb_up' : 'thumb_down';
                var $stars = $('<ul/>')
                    .addClass('rating-stars');
                
                var $bar = $container.clone()
                    .addClass('rating-card mdl-color--'+ color)
                    .append('<div class="rating-thumbs"><i class="material-icons">'+ thumb +'</i></div>')
                    .append($stars)
                    .appendTo($card);
                
                if (ticket.comment !== '') {
                    $bar.find('.rating-thumbs').after('<i class="material-icons comment-icon mdl-color-text--'+ color +'-300">comment</i>')                    
                }
                
                // Stars
                for (var i = 1; i <= 5; i++) {
                    var colorIcon = (i <= ticket.rate) ? 'mdl-color-text--white' : 'mdl-color-text--'+ color +'-300';
                    
                    $stars.append('<li><i class="material-icons '+ colorIcon +'">star</i></li>');
                }
            }
        });
        
        between.stopwatch();
    },
    showRatingInvitations: function() {
        var $container = $('<div/>')
            .addClass('rating-card-invitation mdl-card__actions mdl-card--border mdl-color--red');
        
        search.result.forEach(function(ticket) {
            if (ticket.rate === null && ticket.status == 'finished') {
                var $card = $('#search-card-'+ ticket.id);                
                var $button = $('<a/>')
                    .addClass('mdl-button mdl-js-button mdl-js-ripple-effect mdl-color-text--white')
                    .attr({ href: Routing.generate('ticket_edit', { number: ticket.number, rating: true }) })
                    .text('Avalie este atendimento');
                
                $container.clone()
                    .append($button)
                    .appendTo($card);
            }
        });
    }
};