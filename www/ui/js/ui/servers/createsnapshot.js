Scalr.regPage('Scalr.ui.servers.createsnapshot', function (loadParams, moduleParams) {
	return Ext.create('Ext.form.Panel', {
		scalrOptions: {
			'modal': true
		},
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 900,
		title: 'Create new role',
		fieldDefaults: {
			msgTarget: 'side',
			anchor: '100%'
		},

		items: [{
			cls: 'scalr-ui-form-field-warning',
			html: moduleParams['showWarningMessage'] || '',
			hidden: ! moduleParams['showWarningMessage']
		}, {
			xtype: 'fieldset',
			title: 'Server details',
			items: [{
				xtype: 'displayfield',
				value: moduleParams['serverId'],
				fieldLabel: 'Server ID'
			}, {
				xtype: 'displayfield',
				value: moduleParams['farmId'],
				fieldLabel: 'Farm ID'
			}, {
				xtype: 'displayfield',
				value: moduleParams['farmName'],
				fieldLabel: 'Farm name'
			}, {
				xtype: 'displayfield',
				value: moduleParams['roleName'],
				fieldLabel: 'Role name'
			}]
		}, {
			xtype: 'fieldset',
			title: 'Replacement options',
			items: [{
				xtype: 'radiogroup',
				columns: 1,
				hideLabel: true,
				listeners: {
					change: function (field, value) {
						if (value['replaceType'] != 'no_replace')
							this.next().show();
						else
							this.next().hide();
					}
				},
				items: [{
					name: 'replaceType',
					boxLabel: moduleParams['replaceNoReplace'],
					inputValue: 'no_replace'
				}, {
					name: 'replaceType',
					boxLabel: moduleParams['replaceFarmReplace'],
					inputValue: 'replace_farm'
				}, {
					name: 'replaceType',
					boxLabel: moduleParams['replaceAll'],
					checked: true,
					inputValue: 'replace_all'
				}]
			}, {
				xtype: 'checkbox',
				name: 'noServersReplace',
				boxLabel: 'Do not replace already running servers. Only NEW servers will be launched using created image.'
			}]
		}, {
			xtype: 'fieldset',
			title: 'Role options',
			items: [{
				xtype: 'textfield',
				name: 'roleName',
				value: moduleParams['roleName'],
				fieldLabel: 'Role name'
			}, {
				xtype: 'textarea',
				fieldLabel: 'Description',
				name: 'roleDescription',
				height: 100
			}, {
				xtype: 'fieldcontainer',
				fieldLabel: 'Root EBS size',
				layout: 'hbox',
				items: [{
					xtype: 'textfield',
					name: 'rootVolumeSize',
					width: 100
				}, {
					padding: {
						left: 5
					},
					xtype: 'displayfield',
					value: 'GB (Leave blank for default value)',
				}],
				hidden: !(moduleParams['platform'] == 'ec2' && moduleParams['isVolumeSizeSupported'] == 1)
			}, {
				xtype: 'hidden',
				name: 'serverId',
				value: moduleParams['serverId']
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
				text: 'Create role',
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'action'
						},
						form: this.up('form').getForm(),
						url: '/servers/xServerCreateSnapshot/',
						success: function () {
							Scalr.event.fireEvent('redirect', '#/bundletasks/view', true);
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
