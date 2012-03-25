Scalr.regPage('Scalr.ui.core.logout', function () {
	var keys = [ 'scalr_client_id','scalr_user_id','scalr_user_group','scalr_hash','scalr_sault','scalr_signature' ], d = new Date();
	for (var i = 0; i < keys.length; i++)
		document.cookie = keys[i] + "='';expires=" + d.toGMTString() + ";;";

	document.location.href = '/';

});
