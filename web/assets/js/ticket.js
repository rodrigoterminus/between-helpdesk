var ticket = {
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
        var dialog = document.querySelector('dialog');
        
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
    }
};