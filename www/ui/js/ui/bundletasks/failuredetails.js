Scalr.regPage('Scalr.ui.bundletasks.failuredetails', function (loadParams, moduleParams) {
	return Ext.create('Ext.panel.Panel', {
		title: 'Bundle task information',
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
		items: [{
			xtype: 'fieldset',
			title: 'General information',
			labelWidth: 130,
			items: [{
				xtype: 'displayfield',
				name: 'email',
				fieldLabel: 'Failure reason',
				readOnly: true,
				value: '<span style="color:red;">' + moduleParams['failureReason'] + '</span>'
			}]
		}],
		width: 800
	});
});
