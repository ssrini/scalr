Scalr.regPage('Scalr.ui.core.profile', function (loadParams, moduleParams) {
	return Ext.create('Ext.form.Panel', {
		width: 700,
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		title: 'Profile',
		bodyPadding: {
			left: 5,
			right: 5
		},
		fieldDefaults: {
			anchor: '100%',
			labelWidth: 130,
		},
		items: [{
			xtype: 'fieldset',
			title: 'General information',
			items: [{
				xtype: 'displayfield',
				name: 'email',
				fieldLabel: 'Email',
				readOnly: true,
				value: moduleParams['email']
			},{
				xtype: 'textfield',
				inputType:'password',
				name: 'password',
				allowBlank: false,
				fieldLabel: 'Password',
				value: '******'
			},{
				xtype: 'textfield',
				inputType:'password',
				name: 'cpassword',
				allowBlank: false,
				fieldLabel: 'Confirm password',
				value: '******'
			},{
				xtype: 'textfield',
				name: 'fullname',
				fieldLabel: 'Full name',
				value: moduleParams['fullname']
			}]
		}],

		dockedItems: [{
			xtype: 'container',
			cls: 'scalr-ui-docked-bottombar',
			dock: 'bottom',
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
						url: '/core/xProfileSave/',
						form: this.up('form').getForm(),
						success: function () {
							//Scalr.event.fireEvent('close');
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
});
