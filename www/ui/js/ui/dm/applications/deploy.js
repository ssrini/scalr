Scalr.regPage('Scalr.ui.dm.applications.deploy', function (loadParams, moduleParams) {
	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 700,
		title: 'Deployments &raquo; Applications &raquo; Deploy',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},

		items: [{
			xtype: 'farmroles',
			title: 'Deploy target',
			enableServerId: false,
			enableAllValue: false,
			params: moduleParams
		}, {
			xtype: 'fieldset',
			title: 'Options',
			itemId: 'options',
			labelWidth: 150,
			items: [{
				xtype:'textfield',
                allowBlank: false,
				itemId: 'remotePath',
				fieldLabel: 'Remote path',
				name: 'remotePath'
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
				text: 'Deploy',
				handler: function () {
					Scalr.Request({
						processBox: {
							type: 'execute',
							msg: 'Deploying. Please wait ...'
						},
						url: '/dm/applications/xDeploy/',
						params: loadParams,
						form: form.getForm(),
						success: function () {
							Scalr.event.fireEvent('close');
						}
					});
				},
				width: 80
			}, {
				xtype: 'button',
				width: 80,
				margin: {
					left: 5
				},
				text: 'Cancel',
				handler: function() {
					Scalr.event.fireEvent('close');
				}
			}]
		}]
	});

	return form;
});
