Scalr.regPage('Scalr.ui.logs.scriptingmessage', function (loadParams, moduleParams) {
	return Ext.create('Ext.panel.Panel', {
		title: 'Logs &raquo; Scripting &raquo; Message',
		scalrOptions: {
			'modal': true,
			'maximize': 'maxHeight'
		},
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		tools: [{
			id: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}],
		layout: 'anchor',
		defaults: {
			anchor: '100%'
		},
		items: moduleParams,
		autoScroll: true,
		width: 800
	});
});
