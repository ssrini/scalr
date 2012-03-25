Scalr.regPage('Scalr.ui.admin.utils.mapPermissions', function (loadParams, moduleParams) {
	var result = [];
	for (var c in moduleParams['map']) {
		if (Ext.isArray(moduleParams['map'][c]))
			result.push({
				controller: c,
				methods: moduleParams['map'][c]
			});
		else
			result.push({
				controller: c,
				message: moduleParams['map'][c]
			});
	}

	return Ext.create('Ext.panel.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 800,
		title: 'Admin &raquo; Utils &raquo; Permissions Map',
		data: result,
		tpl:
			'<tpl for=".">' +
				'<tpl if="Ext.isString(message)">' +
					'<span style="color: gray;">{controller} = {message}</span><br />' +
				'</tpl>' +
				'<tpl if="Ext.isArray(methods)">' +
					'{controller}' +
					'<tpl for="methods">' +
						'<br />' +
						'<tpl if="permission">&nbsp;&nbsp;&nbsp;<span style="color: green">{name} = {permission}</span></tpl>' +
						'<tpl if="!permission">&nbsp;&nbsp;&nbsp;<span style="color: red">{name}</span></tpl>' +
					'</tpl>' +
					'<br />' +
				'</tpl>' +
			'</tpl>'
	});
});
