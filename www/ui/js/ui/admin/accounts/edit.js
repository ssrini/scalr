Scalr.regPage('Scalr.ui.admin.accounts.edit', function (loadParams, moduleParams) {
	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 700,
		title: 'Accounts &raquo; ' + (moduleParams['account']['id'] ? ('Edit &raquo; ' + moduleParams['account']['name']) : 'Create'),
		fieldDefaults: {
			anchor: '100%',
			labelWidth: 130,
			msgTarget: 'side'
		},
		items: [{
			xtype: 'fieldset',
			title: 'General information',
			items: [{
				xtype: 'textfield',
				name: 'name',
				fieldLabel: 'Name'
			}, {
				xtype: 'textarea',
				name: 'comments',
				fieldLabel: 'Comments'
			}]
		}, {
			xtype: 'fieldset',
			title: 'Owner information',
			hidden: (moduleParams['account']['id']),
			items: [{
				xtype: 'textfield',
				name: 'ownerEmail',
				fieldLabel: 'Email'
			}, {
				xtype: 'textfield',
				name: 'ownerPassword',
				fieldLabel: 'Password'
			}]
		}, {
			xtype: 'fieldset',
			title: 'Limits',
			hidden: (!moduleParams['account']['id']),
			items: [{
				xtype: 'textfield',
				name: 'limitEnv',
				fieldLabel: 'Environments'
			}, {
				xtype: 'textfield',
				name: 'limitUsers',
				fieldLabel: 'Users'
			}, {
				xtype: 'textfield',
				name: 'limitFarms',
				fieldLabel: 'Farms'
			}, {
				xtype: 'textfield',
				name: 'limitServers',
				fieldLabel: 'Servers'
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
				text: 'Save',
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						url: '/admin/accounts/xSave',
						form: this.up('form').getForm(),
						params: {
							id: moduleParams['account']['id']
						},
						success: function () {
							Scalr.event.fireEvent('close');
						}
					});	
				}
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
	
	form.getForm().setValues(moduleParams['account']);
	
	return form;
});
