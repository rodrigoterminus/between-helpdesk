var ticket = {
	init: function() {
		$(document).ready(function() {

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