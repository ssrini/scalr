Scalr.regPage('Scalr.ui.servers.operationdetails', function (loadParams, moduleParams) {
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
		title: 'Server initialization progress',
		items:[{
			xtype: 'fieldset',
			title: 'General information',
			labelWidth: 130,
			items: [{
				xtype: 'displayfield',
				fieldLabel: 'Server ID',
				value: loadParams['serverId']
			}, {
				xtype: 'displayfield',
				fieldLabel: 'Status',
				value: moduleParams['status']
			}, {
				xtype: 'displayfield',
				fieldLabel: 'Error',
				hidden: !(moduleParams['message']),
				value: moduleParams['message']
			}]
		}, {
			xtype: 'fieldset',
			title: 'Details',
			html: moduleParams['content']
		}],
		autoScroll: true,
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
