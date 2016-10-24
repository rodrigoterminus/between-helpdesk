var ticket = {
    id: null,
    number: null,
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
        
//        $('#btn-add-comment').on('click', ticket.openCommentDialog);
        $('#btn-send-comment').on('click', function() {
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
        $('#btn-toggle-comments').on('click', ticket.toggleComments)
    },
    finish: function () {
        if (confirm('Confirma o fechamento deste chamado?')) {
            window.location = window.location.href + '/finish';
        }
    },
    reopen: function () {
        if (confirm('Confirma a reabertura deste chamado?')) {
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
            alert('Selecione um atendente para transferir o chamado');
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
                    notifier.notify({message: 'Você está assistindo este chamado.'}, true);
                }
                else {
                    watcher.icon = 'visibility';
                    watcher.label = 'Acompanhar este chamado';
                    $watchers.find('.ticket-watcher[data-id='+ response.user.id +']').remove();
                    notifier.notify({message: 'Você deixou de assistir este chamado.'}, true);
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
                notifier.notify({message: 'Não foi possível te inscrever neste chamado.'}, true);
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
    }
};