var project = {
	init: function (){
		$(document).ready(function (){
			
		})
	},
	showRemoveDialog: function(formName) {
		between.showConfirmationDialog({
			title: 'Atenção',
			message: 'Confirma a exclusão deste projeto?',
			onConfirm: function() {
				between.submitForm(formName)
			}
		})
	}
}