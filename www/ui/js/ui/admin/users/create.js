Scalr.regPage('Scalr.ui.admin.users.create', function (loadParams, moduleParams) {
	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 700,
		title: (moduleParams['user']) ? 'Account &raquo; Users &raquo; Edit' : 'Account &raquo; Users &raquo; Create',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},

		items: [{
			xtype: 'fieldset',
			title: 'General information',
			items: [{
				xtype: 'textfield',
				name: 'email',
				fieldLabel: 'Email',
				allowBlank: false
			}, {
				xtype: 'textfield',
				name: 'password',
				inputType: 'password',
				fieldLabel: 'Password',
				value: moduleParams['user'] ? '******': '',
				allowBlank: false
			}, {
				xtype: 'radiogroup',
				fieldLabel: 'Status',
				allowBlank: false,
				columns: 7,
				items: [{
					name: 'status',
					inputValue: 'Active',
					boxLabel: 'Active',
					checked: true
				}, {
					name: 'status',
					inputValue: 'Inactive',
					boxLabel: 'Inactive'
				}]
			}, {
				xtype: 'textfield',
				name: 'fullname',
				fieldLabel: 'Full name'
			}, {
				xtype: 'textarea',
				name: 'comments',
				fieldLabel: 'Comments',
				grow: true,
				growMax: 400
			}, {
				xtype: 'hidden',
				name: 'id'
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
				text: moduleParams['user'] ? 'Save' : 'Create',
				handler: function () {
					if (form.getForm().isValid())
						Scalr.Request({
							processBox: {
								type: 'save'
							},
							url: '/admin/users/xSave',
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
	
	if (moduleParams['user'])
		form.getForm().setValues(moduleParams['user']);

	return form;
});
