Scalr.regPage('Scalr.ui.logs.apilogentrydetails', function (loadParams, moduleParams) {
	return Ext.create('Ext.panel.Panel', {
		title: 'Logs &raquo; API &raquo; Entry details',
		scalrOptions: {
			'modal': true
		},
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		tools: [{
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}],
		layout: 'anchor',
		defaults: {
			anchor: '100%'
		},
		items: moduleParams,
		autoScroll: true
	});
});
