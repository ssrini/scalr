Scalr.regPage('Scalr.ui.dm.tasks.failuredetails', function (loadParams, moduleParams) {
	var form = new Ext.form.FormPanel({
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width:700,
		title: 'Deploy task information',
		items: [{
			xtype: 'fieldset',
			title: 'General information',
			labelWidth: 130,
			items: [{
				xtype: 'displayfield',
				name: 'email',
				fieldLabel: 'Failure reason',
				readOnly:true,
				anchor:"-20",
				value: '<span style="color:red;">' + moduleParams['last_error'] + '</span>'
			}]
		}],
		
		dockedItems: [{
			xtype: 'container',
			dock: 'bottom',
			cls: 'scalr-ui-docked-bottombar',
			layout: {
				type: 'hbox',
				pack: 'center'
			},
			items: [{
				xtype: 'button',
				width: 80,
				margin: {
					left: 5
				},
				text: 'Close',
				handler: function() {
					Scalr.event.fireEvent('close');
				}
			}]
		}]
	});

	return form;
});
