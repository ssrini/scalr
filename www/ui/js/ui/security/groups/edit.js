Scalr.regPage('Scalr.ui.security.groups.edit', function (loadParams, moduleParams) {
	var form = Ext.create('Ext.form.Panel', {
		bodyCls: 'scalr-ui-frame',
		width: 900,
		layout: 'card',
		title: 'Security &raquo; Groups &raquo; '+((moduleParams['securityGroupId']) ? moduleParams['securityGroupId']+' &raquo; Edit' : 'Create'),
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},

		items: [{
				xtype: 'grid',
				itemId: 'view',
				border: false,
				store: {
					proxy: 'object',
					fields: ['id', 'ipProtocol', 'fromPort', 'toPort' , 'cidrIp', 'comment']
				},
				plugins: {
					ptype: 'gridstore'
				},

				viewConfig: {
					emptyText: 'No security rules defined',
					deferEmptyText: false
				},

				columns: [
					{ header: 'Protocol', flex: 120, sortable: true, dataIndex: 'ipProtocol' },
					{ header: 'From port', flex: 120, sortable: true, dataIndex: 'fromPort' },
					{ header: 'To port', flex: 120, sortable: true, dataIndex: 'toPort' },
					{ header: 'CIDR IP', flex: 200, sortable: true, dataIndex: 'cidrIp' },
					{ header: 'Comment', flex: 300, sortable: true, dataIndex: 'comment' },
					{ header: '&nbsp;', width: 30, sortable: false, dataIndex: 'id', align:'left', xtype: 'templatecolumn',
						tpl: '<img class="delete" src="/ui/images/icons/delete_icon_16x16.png">'
					}
				],

				listeners: {
					itemclick: function (view, record, item, index, e) {
						if (e.getTarget('img.delete'))
							view.store.remove(record);
					}
				},

				dockedItems: [{
					xtype: 'toolbar',
					dock: 'top',
					layout: {
						type: 'hbox',
						align: 'left',
						pack: 'start'
					},
					items: [{
						icon: '/ui/images/icons/add_icon_16x16.png', // icons can also be specified inline
						cls: 'x-btn-icon',
						tooltip: 'Add new security rule',
						handler: function () {
							Scalr.Confirm({
								form: [{
									xtype: 'combo',
									name: 'ipProtocol',
									fieldLabel: 'Protocol',
									labelWidth: 120,
									editable: false,
									store: [ 'tcp', 'udp', 'icmp' ],
									value: 'tcp',
									queryMode: 'local',
									allowBlank: false
								}, {
									xtype: 'textfield',
									name: 'fromPort',
									fieldLabel: 'From port',
									labelWidth: 120,
									allowBlank: false,
									validator: function (value) {
										if (value < -1 || value > 65535) {
												return 'Valid ports are - 1 through 65535';
										}
										return true;
									}
								}, {
									xtype: 'textfield',
									name: 'toPort',
									fieldLabel: 'To port',
									labelWidth: 120,
									allowBlank: false,
									validator: function (value) {
										if (value < -1 || value > 65535) {
												return 'Valid ports are - 1 through 65535';
										}
										return true;
									}
								}, {
									xtype: 'textfield',
									name: 'cidrIp',
									fieldLabel: 'CIDR IP',
									value: '0.0.0.0/0',
									labelWidth: 120,
									allowBlank: false
								}, {
									xtype: 'textfield',
									name: 'comment',
									fieldLabel: 'Comment',
									value: '',
									labelWidth: 120,
									allowBlank: true
								}],
								ok: 'Add',
								title: 'Add security rule',
								formValidate: true,
								closeOnSuccess: true,
								scope: this,
								success: function (formValues) {
									var view = this.up('#view'), store = view.store;

									if (store.findBy(function (record) {
										if (
											record.get('ipProtocol') == formValues.ipProtocol &&
											record.get('fromPort') == formValues.fromPort &&
											record.get('toPort') == formValues.toPort &&
											record.get('cidrIp') == formValues.cidrIp
										) {
											Scalr.message.Error('Such rule exists');
											return true;
										}
									}) == -1) {
										store.add(formValues);
										return true;
									} else {
										return false;
									}
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
				text: 'Save',
				width: 80,
				handler: function() {
					var data = [];
					Ext.each (form.down('#view').store.getRange(), function (item) {
						data.push(item.data);
					});
					
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						url: '/security/groups/xSave/',
						params: Ext.applyIf(loadParams, {'rules': Ext.encode(data)})
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

	form.down('#view').store.load({ data: moduleParams.rules });

	return form;
});
