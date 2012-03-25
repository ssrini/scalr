Scalr.regPage('Scalr.ui.dm.sources.create', function(loadParams, moduleParams) {
	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 900,
		title: 'Deployments &raquo; Sources &raquo; ' + ((moduleParams['id']) ? 'Edit' : 'Create'),
        scalrOptions: {
            'modal': true
        },
		fieldDefaults: {
			anchor: '100%'
		},
		items: [{
			xtype: 'fieldset',
			title: 'General information',
			labelWidth: 130,
			items: [{
				fieldLabel: 'Type',
				itemId: 'sourceType',
				xtype: 'combo',
				allowBlank: false,
				editable: false,
				store: ['svn', 'http', 'git'],
				value: 'svn',
				name: 'type',
				queryMode: 'local',
				listeners: {
					'change': function() {
						if(this.getValue() == 'svn') {
							form.down('#sshAuth').hide();
							form.down('#passwdAuth').show();
						} else {
							form.down('#sshAuth').hide();
							form.down('#passwdAuth').hide();
						}
						if(this.getValue() == 'git')
							form.down('#sshAuth').show();
					}
				}
			}, {
				xtype: 'textfield',
				name: 'url',
                allowBlank: false,
				fieldLabel: 'URL'
			}]
		}, {
			xtype: 'fieldset',
			title: 'SSH information',
			itemId: 'sshAuth',
			labelWidth: 130,
			hidden: true,
			items: [{
				xtype: 'textarea',
				name: 'sshPrivateKey',
				fieldLabel: 'Private Key'
			}]
		}, {
			xtype: 'fieldset',
			itemId: 'passwdAuth',
			title: 'Authentication information',
			labelWidth: 130,
			items: [{
				xtype: 'textfield',
				name: 'login',
				fieldLabel: 'Login'
			}, {
				xtype: 'textfield',
				name: 'password',
				itemId: 'sourcePassword',
				fieldLabel: 'Password',
				inputType: 'password'
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
                formBind: true,
				text: 'Save',
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						form: form.getForm(),
						url: '/dm/sources/save/',
						params: loadParams,
						success: function(data) {
                            Scalr.event.fireEvent('update', '/dm/applications/create', data.source, 'create');
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
	if(moduleParams['source']) {
		form.getForm().setValues(moduleParams['source']);
		form.getForm().setValues(moduleParams['source']['authInfo']);
		if(moduleParams['source']['authInfo']['password'])
			form.down('#sourcePassword').setValue('******');
	}
	return form;
});
