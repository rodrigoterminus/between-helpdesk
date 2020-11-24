var category = {
	init: function (){
		$(document).ready(function (){
			
		})
	},
	showRemoveDialog: function(formName) {
		between.showConfirmationDialog({
			title: 'Atenção',
			message: 'Confirma a exclusão desta categoria?',
			onConfirm: function() {
				between.submitForm(formName)
			}
		})
	}
}