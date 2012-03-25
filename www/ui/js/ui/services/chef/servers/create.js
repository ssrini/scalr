Scalr.regPage('Scalr.ui.services.chef.servers.create', function (loadParams, moduleParams) {
	var form = Ext.create('Ext.form.Panel', {
		title: loadParams['servId'] ? 'Edit Chef server' : 'Create new Chef server',
		width: 680,
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		scalrOptions: {
			'modal': true
		},
		defaults: {
			anchor: '100%'
		},
		items: [{
			xtype: 'textfield',
			name: 'url',
			fieldLabel: 'URL',
			labelWidth: 125,
			allowBlank: false
		},{
			xtype: 'fieldset',
			title: 'Client auth info',
			defaults: {
				anchor: '100%'
			},
			items: [{
				xtype: 'textfield',
				name: 'userName',
				fieldLabel: 'Username',
				labelWidth: 120,
				allowBlank: false
			},{
				xtype: 'textarea',
				height: 200,
				name: 'authKey',
				fieldLabel: 'Key',
				labelWidth: 120,
				allowBlank: false
			}],
		},{
			xtype: 'fieldset',
			title: 'Client validator auth info',
			defaults: {
				anchor: '100%'
			},
			items: [{
				xtype: 'textfield',
				name: 'userVName',
				fieldLabel: 'Username',
				labelWidth: 120,
				allowBlank: false
			},{
				xtype: 'textarea',
				height: 200,
				name: 'authVKey',
				fieldLabel: 'Key',
				labelWidth: 120,
				allowBlank: false
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
				text: loadParams['servId'] ? 'Save' : 'Add',
				formBind: true,
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'action'
						},
						scope: this,
						form: form.getForm(),
						url: '/services/chef/servers/xSaveServer',
						params: {servId: loadParams['servId'] ? loadParams['servId'] : 0},
						success: function (data) {
							Scalr.event.fireEvent('close');
						}
					});
				}
			},{
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
	if(loadParams['servId'])
		form.getForm().setValues(moduleParams['servParams']);
	return form;
});