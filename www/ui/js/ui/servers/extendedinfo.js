Scalr.regPage('Scalr.ui.servers.extendedinfo', function (loadParams, moduleParams) {
	return Ext.create('Ext.form.Panel', {
		bodyPadding: {
			left: 5,
			right: 5
		},
		scalrOptions: {
			'modal': true
		},
		width: 900,
		bodyCls: 'scalr-ui-frame',
		title: 'Server "' + loadParams['serverId'] + '" extended information',
		fieldDefaults: {
			labelWidth: 160
		},
		items: moduleParams,
		tools: [{
			type: 'refresh',
			handler: function () {
				Scalr.event.fireEvent('refresh');
			}
		}, {
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}]
	});
});
