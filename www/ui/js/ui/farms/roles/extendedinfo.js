Scalr.regPage('Scalr.ui.farms.roles.extendedinfo', function (loadParams, moduleParams) {
	return Ext.create('Ext.form.Panel', {
		title: 'Farms &raquo; ' + moduleParams['farmName'] + ' &raquo; ' + moduleParams['roleName'] + ' &raquo; Extended information',
		scalrOptions: {
			'modal': true
		},
		width: 900,
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		autoScroll: true,
		fieldDefaults: {
			msgTarget: 'side',
			anchor: '100%'
		},
		tools: [{
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}],
		items: moduleParams['form']
	});
});
