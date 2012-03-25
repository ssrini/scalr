Scalr.regPage('Scalr.ui.farms.extendedinfo', function (loadParams, moduleParams) {
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
		title: 'Farm "' + moduleParams['name'] + '" extended information',
		fieldDefaults: {
			labelWidth: 160
		},
		items: moduleParams['info'],
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
