var user = {
    init: function () {
        $(document).ready(function () {
            $('form:eq(0)').on('submit', function () {
                if ($('#appbundle_user_role').val() === 'ROLE_DEFAULT' && $('#appbundle_user_customer').val() === '') {
                    alert('É necessário vincular um cliente aos usuários com o nível de acesso de cliente.');
                    return false;
                }
            });
        });
    },    
    preferences: function() {
        var preferences = {
            notifications: {
                email: {}
            }
        };
        
        $('#notifications input[type=checkbox]').each(function() {
            var $this = $(this);
            var label = $this.attr('id').replace('checkbox-', '');
            
            if ($this.is(':checked')) {
                preferences.notifications.email[label] = true;
            }
            else {
                preferences.notifications.email[label] = false;
            }
        });
        
        $.post(Routing.generate('user_preferences'), { preferences: JSON.stringify(preferences) })
            .done(function() {
                notifier.notify({message: 'Preferências salvas com sucesso!'}, true);
            })
            .fail(function() {
                notifier.notify({message: 'Não foi possível salvar suas preferências.'}, true);
            });
    },
    showRemoveDialog: function(formName) {
        between.showConfirmationDialog({
            title: 'Atenção',
            message: 'Confirma a exclusão deste usuário?',
            onConfirm: function() {
                between.submitForm(formName)
            }
        })
    }
}