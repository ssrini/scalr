Scalr.regPage('Scalr.ui.environments.platform.eucalyptus', function (loadParams, moduleParams) {
	var params = moduleParams['params'];

	var form = Ext.create('Ext.form.Panel', {
		scalrOptions: {
			'modal': true
		},
		width: 900,
		title: 'Environments &raquo; ' + moduleParams.env.name + ' &raquo; Eucalyptus',

		layout: 'card',
		activeItem: 0,

		addCardTab: function (region, values) {
			values = values || {};

			var record = this.down('#view').store.add({
				region: region,
				account_id: values['eucalyptus.account_id'] || '',
				ec2_url: values['eucalyptus.ec2_url'] || '',
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
					fieldLabel: 'Account ID',
					name: 'eucalyptus.account_id.' + region,
					value: values['eucalyptus.account_id'] || '',
					listeners: {
						change: function (field, newValue) {
							record.set('account_id', newValue);
						}
					}
				}, {
					xtype: 'textfield',
					fieldLabel: 'Access Key',
					name: 'eucalyptus.access_key.' + region,
					value: values['eucalyptus.access_key'] || ''
				}, {
					xtype: 'textfield',
					fieldLabel: 'Secret Key',
					name: 'eucalyptus.secret_key.' + region,
					value: values['eucalyptus.secret_key'] || ''
				}, {
					xtype: 'textfield',
					fieldLabel: 'EC2 URL (eg. http://192.168.1.1:8773/services/Eucalyptus)',
					name: 'eucalyptus.ec2_url.' + region,
					value: values['eucalyptus.ec2_url'] || '',
					listeners: {
						change: function (field, newValue) {
							record.set('ec2_url', newValue);
						}
					}
				}, {
					xtype: 'textfield',
					fieldLabel: 'S3 URL (eg. http://192.168.1.1:8773/services/Walrus)',
					name: 'eucalyptus.s3_url.' + region,
					value: values['eucalyptus.s3_url'] || ''
				}, {
					xtype: 'filefield',
					fieldLabel: 'X.509 Certificate file',
					name: 'eucalyptus.certificate.' + region,
					value: values['eucalyptus.certificate'] || ''
				}, {
					xtype: 'filefield',
					fieldLabel: 'X.509 Private Key file',
					name: 'eucalyptus.private_key.' + region,
					value: values['eucalyptus.private_key'] || ''
				}, {
					xtype: 'filefield',
					fieldLabel: 'X.509 Cloud certificate file',
					name: 'eucalyptus.cloud_certificate.' + region,
					value: values['eucalyptus.cloud_certificate'] || ''
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
				fields: [ 'region', 'account_id', 'ec2_url', 'invalid' ],
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
				{ header: "Account ID", flex: 100, dataIndex: 'account_id' },
				{ header: "EC2 URL", flex: 400, dataIndex: 'ec2_url' },
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

							form.down('#buttonCancel').show();
							form.down('#buttonAnotherCloud').hide();
							form.down('#buttonLater').hide();
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
								fieldLabel: 'Eucalyptus Cloud Location',
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

								this.up('form').down('#buttonCancel').show();
								this.up('form').down('#buttonAnotherCloud').hide();
								this.up('form').down('#buttonLater').hide();
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
						url: '/environments/' + moduleParams.env.id + '/platform/xSaveEucalyptus',
						success: function (data) {
							var flag = Scalr.flags.needEnvConfig && data.enabled;
							Scalr.event.fireEvent('update', '/environments/' + moduleParams.env.id + '/edit', 'eucalyptus', data.enabled);
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

					if (Scalr.flags.needEnvConfig) {
						this.next('#buttonCancel').hide();
						this.next('#buttonAnotherCloud').show();
						this.next('#buttonLater').show();
					}
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

					if (Scalr.flags.needEnvConfig) {
						this.next('#buttonCancel').hide();
						this.next('#buttonAnotherCloud').show();
						this.next('#buttonLater').show();
					}
				}
			}, {
				xtype: 'button',
				itemId: 'buttonCancel',
				width: 80,
				margin: {
					left: 5
				},
				hidden: Scalr.flags.needEnvConfig,
				text: 'Cancel',
				handler: function() {
					var item = this.up('panel').layout.getActiveItem();
					if (item.sType == 'add') {
						this.up('panel').layout.setActiveItem(this.up('panel').down('#view'));
						this.up('panel').remove(item);
						this.prev('#buttonAdd').hide();
						this.prev('#buttonSave').show();

						if (Scalr.flags.needEnvConfig) {
							this.hide();
							this.next('#buttonAnotherCloud').show();
							this.next('#buttonLater').show();
						}
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
			}, {
				xtype: 'button',
				itemId: 'buttonAnotherCloud',
				width: 300,
				hidden: !Scalr.flags.needEnvConfig,
				margin: {
					left: 5
				},
				text: "I'm not using Eucalyptus, let me configure another cloud",
				handler: function () {
					Scalr.event.fireEvent('redirect', '#/environments/' + moduleParams.env.id + '/edit', false, true);
				}
			}, {
				xtype: 'button',
				itemId: 'buttonLater',
				width: 80,
				hidden: !Scalr.flags.needEnvConfig,
				margin: {
					left: 5
				},
				text: 'Do this later',
				handler: function () {
					sessionStorage.setItem('needEnvConfigLater', true);
					Scalr.event.fireEvent('unlock');
					Scalr.event.fireEvent('redirect', '#/dashboard');
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
