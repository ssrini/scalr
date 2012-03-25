Scalr.regPage('Scalr.ui.environments.platform.openstack', function (loadParams, moduleParams) {
	var params = moduleParams['params'];

	var form = Ext.create('Ext.form.Panel', {
		scalrOptions: {
			'modal': true
		},
		width: 900,
		title: 'Environments &raquo; ' + moduleParams.env.name + ' &raquo; Openstack',

		layout: 'card',
		activeItem: 0,

		addCardTab: function (region, values) {
			values = values || {};

			var record = this.down('#view').store.add({
				region: region,
				api_url: values['openstack.api_url'] || '',
				username: values['openstack.username'] || '',
				invalid: false
			})[0];

			return this.add({
				xtype: 'form',
				border: false,
				bodyPadding: 5,
				bodyCls: 'scalr-ui-frame',
				layout: 'anchor',
				sType: 'add',
				defaults: {
					anchor: '100%',
					msgTarget: 'side',
					labelWidth: 350
				},
				regionName: region,
				items: [{
					xtype: 'textfield',
					readOnly: true,
					fieldLabel: 'Cloud Location',
					value: region
				}, {
					xtype: 'textfield',
					fieldLabel: 'Username',
					name: 'openstack.username.' + region,
					value: values['openstack.username'] || '',
					listeners: {
						change: function (field, newValue) {
							record.set('username', newValue);
						}
					}
				}, {
					xtype: 'textfield',
					fieldLabel: 'API Key',
					name: 'openstack.api_key.' + region,
					value: values['openstack.api_key'] || ''
				}, {
					xtype: 'textfield',
					fieldLabel: 'Project name',
					name: 'openstack.project_name.' + region,
					value: values['openstack.project_name'] || ''
				}, {
					xtype: 'textfield',
					fieldLabel: 'API URL (eg. http://openstack.mycompany.com:8774)',
					name: 'openstack.api_url.' + region,
					value: values['openstack.api_url'] || '',
					listeners: {
						change: function (field, newValue) {
							record.set('api_url', newValue);
						}
					}
				}],

				listeners: {
					removed: function (comp, cont) {
						if (cont.down('#view'))
							cont.down('#view').store.remove(record);
					},
					hide: function () {
						record.set('invalid', false);
						this.form.getFields().findBy(function(field) {
							field.resetOriginalValue();
						});
					}
				}
			});
		},

		items: [{
			xtype: 'grid',
			itemId: 'view',
			border: false,
			sType: 'view',
			store: {
				fields: [ 'region', 'username', 'api_url', 'invalid' ],
				proxy: 'object'
			},
			plugins: {
				ptype: 'gridstore'
			},

			viewConfig: {
				emptyText: 'No cloud locations found',
				deferEmptyText: false,
				getRowClass: function (record) {
					return record.get('invalid') ? 'scalr-ui-grid-row-red' : '';
				}
			},

			columns: [
				{ header: "Cloud Location", flex: 100, dataIndex: 'region' },
				{ header: "Username", flex: 100, dataIndex: 'username' },
				{ header: "API URL", flex: 400, dataIndex: 'api_url' },
				{
					xtype: 'optionscolumn',
					optionsMenu: [{
						iconCls: 'scalr-menu-icon-configure',
						text: 'Edit',
						menuHandler: function (item) {
							form.layout.setActiveItem(
								form.down('[regionName="' + item.record.get('region') + '"]')
							);

							form.down('[regionName="' + item.record.get('region') + '"]').sType = 'location';
							form.down('#buttonSave').hide();
							form.down('#buttonEdit').show();
						}
					}, {
						iconCls: 'scalr-menu-icon-delete',
						text: 'Delete',
						handler: function (item) {
							form.remove(
								form.down('[regionName="' + item.record.get('region') + '"]')
							);
							delete form.form._fields;
						}
					}]
				}
			],

			dockedItems: [{
				xtype: 'toolbar',
				dock: 'top',
				items: [{
					xtype: 'button',
					icon: '/ui/images/icons/add_icon_16x16.png',
					handler: function() {
						Scalr.Confirm({
							form: [{
								xtype: 'textfield',
								fieldLabel: 'Openstack Cloud Location',
								name: 'location',
								allowBlank: false,
								//regex: /[^-]+[A-Za-z0-9-]+[^-]/, @TODO
								labelWidth: 160
							}],
							ok: 'Add',
							title: 'Please specify cloud location name',
							formValidate: true,
							formWidth: 500,
							scope: this.up('#view'),
							success: function (formValues) {
								var c = this.up('form').addCardTab(formValues.location);
								this.up('form').layout.setActiveItem(c);
								this.up('form').down('#buttonSave').hide();
								this.up('form').down('#buttonAdd').show();
							}
						});
					}
				}]
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
				itemId: 'buttonSave',
				text: 'Save',
				width: 80,
				handler: function() {
					var data = [];
					Ext.each (form.down('#view').store.getRange(), function (item) {
						data.push(item.get('region'));
					});

					Scalr.Request({
						processBox: {
							type: 'save'
						},
						params: {
							clouds: Ext.encode(data)
						},
						form: form.getForm(),
						url: '/environments/' + moduleParams.env.id + '/platform/xSaveOpenstack',
						success: function (data) {
							var flag = Scalr.flags.needEnvConfig && data.enabled;
							Scalr.event.fireEvent('update', '/environments/' + moduleParams.env.id + '/edit', 'openstack', data.enabled);
							if (! flag)
								Scalr.event.fireEvent('close');
						},
						failure: function (data, response, options) {
							if (options.failureType == 'server') {
								form.down('#view').store.each(function (record) {
									record.set('invalid',
										!!form.down('[regionName="' + record.get('region') + '"]').form.getFields().findBy(function(field) {
											if (field.getActiveError())
												return true;
										})
									);
								});
							}
						}
					});
				}
			}, {
				xtype: 'button',
				itemId: 'buttonAdd',
				text: 'Add',
				width: 80,
				hidden: true,
				handler: function () {
					this.up('panel').layout.setActiveItem(this.up('panel').down('#view'));
					this.hide();
					this.prev('#buttonSave').show();
				}
			}, {
				xtype: 'button',
				itemId: 'buttonEdit',
				text: 'Edit',
				width: 80,
				hidden: true,
				handler: function () {
					this.up('panel').layout.setActiveItem(this.up('panel').down('#view'));
					this.hide();
					this.prev('#buttonSave').show();
				}
			}, {
				xtype: 'button',
				itemId: 'buttonCancel',
				width: 80,
				margin: {
					left: 5
				},
				text: 'Cancel',
				handler: function() {
					var item = this.up('panel').layout.getActiveItem();
					if (item.sType == 'add') {
						this.up('panel').layout.setActiveItem(this.up('panel').down('#view'));
						this.up('panel').remove(item);
						this.prev('#buttonAdd').hide();
						this.prev('#buttonSave').show();
					} else if (item.sType == 'location') {
						this.up('panel').layout.getActiveItem().form.getFields().findBy(function(field) {
							field.reset();
						});
						this.up('panel').layout.setActiveItem(this.up('panel').down('#view'));
						this.prev('#buttonEdit').hide();
						this.prev('#buttonSave').show();
					} else {
						Scalr.event.fireEvent('close');
					}
				}
			}]
		}]
	});

	if (Ext.isObject(moduleParams['params'])) {
		for (var i in moduleParams['params'])
			form.addCardTab(i, moduleParams['params'][i]);
	}

	return form;
});
