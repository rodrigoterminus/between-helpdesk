var user = {
	init: function (){
		$(document).ready(function (){
			$('form:eq(0)').on('submit', function (){
				if ($('#appbundle_user_role').val() == 'ROLE_DEFAULT' && $('#appbundle_user_customer').val() == '') {
					alert('É necessário vincular um cliente aos usuários com o nível de acesso de cliente.');
					return false;
				}
			})
		})
	}
}