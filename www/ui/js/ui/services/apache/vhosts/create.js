Scalr.regPage('Scalr.ui.services.apache.vhosts.create', function (loadParams, moduleParams) {
	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		title: 'Services &raquo; Apache &raquo; Vhosts &raquo; Create',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},

		items: [{
			xtype: 'fieldset',
			title: 'General',
			items: [{
				xtype: 'textfield',
				name: 'domainName',
				fieldLabel: 'Domain name',
				value: moduleParams['domainName']
			}]
		}, {
			xtype: 'fieldset',
			title: 'Create virtualhost on',
			layout: 'column',
			itemId: 'vhostTarget',
			items: [{
				xtype: 'combo',
				hideLabel: true,
				name: 'farmId',
				store: {
					fields: [ 'id', 'name' ],
					data: moduleParams['farms'],
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				emptyText: 'Select a farm',
				columnWidth: .33,
				editable: false,
				value: moduleParams['farmId'],
				queryMode: 'local',
				listeners: {
					select: function (field) {
						Scalr.Request({
							url: '/farms/roles/getList/',
							params: { farmId: field.getValue(), behaviors:Ext.encode(['app']) },
							processBox: {
								type: 'load',
								msg: 'Loading farm roles. Please wait ...'
							},
							success: function (data) {
								var field = form.down('[name="farmRoleId"]');
								field.show();
								if (Ext.isObject(data.farmRoles)) {
									field.emptyText = 'Select a role';
									field.reset();
									field.store.load({ data: data.farmRoles });
									field.setValue(0);
									field.enable();
								} else {
									field.store.removeAll();
									field.emptyText = 'No roles';
									field.reset();
									field.disable();
								}
							}
						});
					}
				}
			}, {
				xtype: 'combo',
				hideLabel: true,
				name: 'farmRoleId',
				store: {
					fields: [ 'id', 'name', 'platform', 'role_id' ],
					data: moduleParams['farmRoles'],
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				emptyText: 'Select a role',
				columnWidth: .33,
				margin: {
					left: 5
				},
				editable: false,
				value: moduleParams['farmRoleId'],
				queryMode: 'local',
				hidden: moduleParams['farmRoleId'] ? false : true,
			}]
		} , {
			xtype: 'fieldset',
			title: 'SSL',
			checkboxToggle:  true,
			collapsed: !moduleParams['isSslEnabled'],
			checkboxName: 'isSslEnabled',
			inputValue: 1,
			items: [{
					xtype: 'filefield',
					name: 'certificate',
					fieldLabel: 'Certificate',
					value: moduleParams['sslCertName']
				}, {
					xtype: 'filefield',
					name: 'privateKey',
					fieldLabel: 'Private key',
					value: moduleParams['sslKeyName']
				},
				{
					xtype: 'filefield',
					name: 'certificateChain',
					fieldLabel: 'Certificate chain',
					value: moduleParams['caCertName']
			}],
			listeners: {
				afterrender:function() {
					this.checkboxCmp.on('change', function(){
						if (this.getValue()) {
							form.down('[name="sslTemplate"]').show();
						} else {
							form.down('[name="sslTemplate"]').hide();
						}
						
						form.doLayout();
					})
				}
			}
		}, {
			xtype: 'fieldset',
			title: 'Settings',
			defaults:{
				labelWidth: 180,
			},
			items: [{
				xtype: 'textfield',
				name: 'documentRoot',
				fieldLabel: 'Document root',
				value: moduleParams['documentRoot']
			}, {
				xtype: 'textfield',
				name: 'logsDir',
				fieldLabel: 'Logs directory',
				value: moduleParams['logsDir']
			}, {
				xtype: 'textfield',
				name: 'serverAdmin',
				fieldLabel: 'Server admin\'s email',
				value: moduleParams['serverAdmin']
			}, {
				xtype: 'textfield',
				name: 'serverAlias',
				fieldLabel: 'Server alias (space separated)',
				value: moduleParams['serverAlias']
			}, {
				xtype: 'textarea',
				name: 'nonSslTemplate',
				fieldLabel: 'Server non-SSL template',
				grow: true,
				growMax: 400,
				value: moduleParams['nonSslTemplate']
			}, {
				xtype: 'textarea',
				name: 'sslTemplate',
				hidden:!moduleParams['isSslEnabled'],
				fieldLabel: 'Server SSL template',
				value: moduleParams['sslTemplate'],
				grow: true,
				growMax: 400
			} ]
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
						form: form.getForm(),
						url: '/services/apache/vhosts/xSave/',
						params: { 'vhostId': moduleParams['vhostId'] },
						success: function () {
							Scalr.event.fireEvent('redirect', '#/services/apache/vhosts', true);
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

	

	return form;
});
