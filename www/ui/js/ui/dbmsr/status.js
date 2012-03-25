Scalr.regPage('Scalr.ui.dbmsr.status', function (loadParams, moduleParams) {
	
	var generalItems = [{
		xtype: 'displayfield',
		name: 'email',
		fieldLabel: 'Database type',
		readOnly: true,
		value: moduleParams['dbType']
	}];
	
	for (k in moduleParams['additionalInfo'])
	{
		generalItems[generalItems.length] = {
			xtype: 'displayfield',
			name: k,
			fieldLabel: k,
			readOnly: true,
			value: moduleParams['additionalInfo'][k]
		};
	}
	
	var panel = Ext.create('Ext.form.Panel', {
		width: 700,
		title: 'Database status',
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		fieldDefaults: {
			anchor: '100%',
			labelWidth: 130
		},
		items: [{
			xtype: 'fieldset',
			title: 'General information',
			items: generalItems
		}, {
			xtype: 'fieldset',
			title: 'DNS endpoints',
			items: [{
				'cls': 'scalr-ui-form-field-info',
				'html': 'Public - To connect to the service from the Internet<br / >Private - To connect to the service from another instance',
				'border': false
			}, {
				xtype: 'displayfield',
				labelWidth: 200,
				fieldLabel: 'Writes endpoint (Public)',
				value: 'ext.master.' + moduleParams['dbType'] + '.' + moduleParams['farmHash'] + '.scalr-dns.net'
			}, {
				xtype: 'displayfield',
				labelWidth: 200,
				fieldLabel: 'Reads endpoint (Public)',
				value: 'ext.slave.' + moduleParams['dbType'] + '.' + moduleParams['farmHash'] + '.scalr-dns.net'
			}, {
				xtype: 'displayfield',
				labelWidth: 200,
				fieldLabel: 'Writes endpoint (Private)',
				value: 'int.master.' + moduleParams['dbType'] + '.' + moduleParams['farmHash'] + '.scalr-dns.net'
			}, {
				xtype: 'displayfield',
				labelWidth: 200,
				fieldLabel: 'Reads endpoint (Private)',
				value: 'int.slave.' + moduleParams['dbType'] + '.' + moduleParams['farmHash'] + '.scalr-dns.net'
			}]
		}, {
			xtype: 'fieldset',
			title: 'PHPMyAdmin access',
			hidden: (moduleParams['dbType'] != 'mysql'),
			items: [{
				xtype: 'button',
				name: 'setupPMA',
				hidden: (moduleParams['pmaAccessConfigured'] || moduleParams['pmaAccessSetupInProgress']),
				text: 'Setup PHPMyAdmin access',
				handler: function(){
					Scalr.Request({
						processBox: {
							type: 'action',
							msg: 'Please wait...'
						},
						url: '/dbmsr/xSetupPmaAccess/',
						params: {farmId: loadParams['farmId'], farmRoleId: moduleParams['farmRoleId']},
						success: function(){
							panel.down('[name="setupPMA"]').hide();
							panel.down('[name="PMAinProgress"]').show();
						}
					});
				}
			}, {
				xtype: 'button',
				name: 'launchPMA',
				margin: {
					left:3
				},
				hidden: (!moduleParams['pmaAccessConfigured']),
				text: 'Launch PHPMyAdmin',
				handler: function() {
					document.location.href = '/externals/pma_redirect.php?farmid='+loadParams['farmId'];
				}
			}, {
				xtype: 'button',
				name: 'resetPMA',
				margin: {
					left:3
				},
				hidden:(!moduleParams['pmaAccessConfigured']),
				text: 'Reset PHPMyAdmin credentials',
				handler: function(){
					Scalr.Request({
						confirmBox: {
							type: 'action',
							msg: 'Are you sure want to reset PMA access?'
						},
						processBox: {
							type: 'action',
							msg: 'Please wait...'
						},
						url: '/dbmsr/xSetupPmaAccess/',
						params: {farmId: loadParams['farmId'], farmRoleId: moduleParams['farmRoleId']},
						success: function(){
							panel.down('[name="setupPMA"]').hide();
							panel.down('[name="launchPMA"]').hide();
							panel.down('[name="resetPMA"]').hide();
							panel.down('[name="PMAinProgress"]').show();
						}
					});
				}
			},{
				xtype: 'displayfield',
				width: 500,
				hidden: (!moduleParams['pmaAccessSetupInProgress']),
				name: 'PMAinProgress',
				value: 'MySQL access details for PMA requested. Please refresh this page in a couple minutes...'
			}]
		}, {
			xtype: 'fieldset',
			title: 'Backups &amp; Data Bundles',
			items: [{
				xtype: 'fieldcontainer',
				fieldLabel: 'Last backup',
				layout: 'hbox',
				items: [{
					xtype: 'displayfield',
					width: 200,
					name: 'backupStatus',
					value: (moduleParams['isBackupRunning'] == 1) ? 'In progress...' : moduleParams['dtLastBackup']
				} , {
					xtype: 'button',
					margin: {
						left:3
					},
					text: 'Create backup',
					handler: function(){
						Scalr.Request({
							confirmBox: {
								type: 'action',
								msg: 'Are you sure want to create backup?'
							},
							processBox: {
								type: 'action',
								msg: 'Sending backup request to the server. Please wait...'
							},
							url: '/dbmsr/xCreateBackup/',
							params: {farmId: loadParams['farmId'], farmRoleId: moduleParams['farmRoleId']},
							success: function(){
								panel.down('[name="backupStatus"]').setValue("In progress...");
							}
						});
					}
				}]
			}, {
				xtype: 'fieldcontainer',
				fieldLabel: 'Last data bundle',
				layout: 'hbox',
				items: [{
					xtype: 'displayfield',
					name: 'dataBundleStatus',
					width: 200,
					value: (moduleParams['isBundleRunning'] == 1) ? 'In progress...' : moduleParams['dtLastBundle']
				}, {
					xtype: 'button',
					margin: {
						left:3
					},
					text: 'Create data bundle',
					handler: function(){
						Scalr.Request({
							confirmBox: {
								type: 'action',
								msg: 'Are you sure want to create data bundle?'
							},
							processBox: {
								type: 'action',
								msg: 'Sending data bundle request to the server. Please wait...'
							},
							url: '/dbmsr/xCreateDataBundle/',
							params: {farmId: loadParams['farmId'], farmRoleId: moduleParams['farmRoleId']},
							success: function(){
								panel.down('[name="dataBundleStatus"]').setValue("In progress...");
							}
						});
					}
				}]
			}]
		}],
		tools: [{
			type: 'refresh',
			handler: function () {
				Scalr.event.fireEvent('refresh');
			}
		}, {
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}]
	});

	Ext.each(moduleParams['replicationStatus'], function(item){
		var items = [{
				xtype: 'displayfield',
				fieldLabel: 'Remote IP',
				labelWidth: 200,
				value: item['remoteIp']
			}, {
				xtype: 'displayfield',
				fieldLabel: 'Local IP',
				labelWidth: 200,
				value: item['localIp']
			}];

		if (item['error']) {
			items[items.length] = {
				xtype: 'displayfield',
				hideLabel: true,
				fieldStyle: {
					color: 'red'
				},
				value: item['error']
			};
		}
		else {
			if (moduleParams['dbType'] == 'mysql') {
				if (item['replicationRole'] == 'Master') {
					items[items.length] = {
						xtype: 'displayfield',
						fieldLabel: 'Binary log position',
						labelWidth: 200,
						fieldStyle: {
							color: 'green'
						},
						value: "<img src='/ui/images/icons/true.png'> " + item['masterPosition']
					};
				}
				else {
					items[items.length] = {
						xtype: 'displayfield',
						fieldLabel: 'Slave_IO_Running',
						labelWidth: 200,
						fieldStyle: {
							color: (item['data']['Slave_IO_Running'] == 'Yes' ? 'green' : 'red')
						},
						value: "<img src='/ui/images/icons/" + (item['data']['Slave_IO_Running'] == 'Yes' ? 'true.png' : 'delete_icon_16x16.png') + "'> " + item['data']['Slave_IO_Running']
					};

					items[items.length] = {
						xtype: 'displayfield',
						fieldLabel: 'Slave_SQL_Running',
						labelWidth: 200,
						fieldStyle: {
							color: (item['data']['Slave_IO_Running'] == 'Yes' ? 'green' : 'red')
						},
						value: "<img src='/ui/images/icons/" + (item['data']['Slave_SQL_Running'] == 'Yes' ? 'true.png' : 'delete_icon_16x16.png') + "'> " + item['data']['Slave_SQL_Running']
					};

					items[items.length] = {
						xtype: 'displayfield',
						fieldLabel: 'Seconds_Behind_Master',
						labelWidth: 200,
						fieldStyle: {
							color: (item['data']['Slave_IO_Running'] == 'Yes' ? 'green' : 'red')
						},
						value: "<img src='/ui/images/icons/" + (item['data']['Seconds_Behind_Master'] == 0 ? 'true.png' : 'delete_icon_16x16.png') + "'> " + item['data']['Seconds_Behind_Master']
					};
				}
			}

			if (item['data'] && item['data'].length > 0) {
				for (key in item['data']) {

					if (key == 'Position' || key == 'Slave_IO_Running' || key == 'Slave_SQL_Running' || key == 'Seconds_Behind_Master')
						continue;

					if (item['data'][key] == '')
						continue;

					items[items.length] = {
						xtype: 'displayfield',
						fieldLabel: key,
						labelWidth: 200,
						value: item['data'][key]
					};
				}
			}
		}

		panel.add({
			xtype: 'fieldset',
			title: item['replicationRole']+": <a href='#/servers/"+item['serverId']+"/extendedInfo'>"+item['serverId']+"</a>",
			items: items
		});
	})

	return panel;
});
