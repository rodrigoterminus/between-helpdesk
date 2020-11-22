var customer = {
	init: function () {
		$(document).ready(function() {

		})
	},
	remove: function() {

	},
	showRemoveDialog: function(customerId, formName) {
		const $dialog = _dialog.create(
			_dialog.title('Atenção!'),
			_dialog.content(
				'<span>Ao excluir este cliente <b>todos os projetos e usuários a ele associados serão excluídos</b>. ' +
				'No entanto, seus <b>tickets permanecerão disponíveis</b>.</span><br><br>' +
				'<span>Deseja confirmar a exclusão deste cliente?</span>'
			),
			_dialog.actions([
				{
					cssClass: 'close',
					label: 'Não excluir',
				},
				{
					cssClass: 'mdl-button--colored',
					label: 'Excluir',
					onClick: () => {
						console.log({formName})
						between.submitForm(formName)
					},
				},
			])
		);

		$('#dialogs').append($dialog);
		$dialog.attr('id', 'dialog-remove-customer');
		$dialog.get(0).showModal()
	}
}