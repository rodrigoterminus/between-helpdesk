var ticket = {
	init: function() {
		$(document).ready(function() {
			$('#appbundle_ticket_customer').on('change', function (){
				between.loadSelectbox($('#appbundle_ticket_project'), 'project_json', { customer: $(this).val() });
			});
		})
	},
	finish: function() {
		if(confirm('Confirma o fechamento deste chamado?')) {
			window.location = window.location.href +'/finish';
		}
	},
	reopen: function() {
		if(confirm('Confirma a reabertura deste chamado?')) {
			window.location = window.location.href +'/reopen';
		}
	}
};