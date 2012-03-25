Scalr.regPage('Scalr.ui.services.chef.runlists.viewsource', function (loadParams, moduleParams) {
	return Ext.create('Ext.panel.Panel', {
		title: 'Chef &raquo; RunList &raquo; Source',
		width: 300,
		height: 300,
		bodyPadding: 5,
		scalrOptions: {
			'modal': true
		},
		bodyCls: 'scalr-ui-frame',
		items: [{
			xtype: 'textareafield',
			value: moduleParams['runlist'],
			labelWidth: 0,
			width: 287,
			height: '98%',
			readOnly: true
		}],
		tools: [{
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}]
	})
});
	