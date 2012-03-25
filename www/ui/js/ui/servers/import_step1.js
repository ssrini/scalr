Scalr.regPage('Scalr.ui.servers.import_step1', function (loadParams, moduleParams) {
	function isValidIPAddress(ipaddr) {
		var re = /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/;
		if (re.test(ipaddr)) {
			var parts = ipaddr.split(".");
			if (parseInt(parseFloat(parts[0])) == 0) { return false; }
			for (var i=0; i<parts.length; i++) {
				if (parseInt(parseFloat(parts[i])) > 255) { return false; }
			}
			return true;
		} else {
			return false;
		}
	}

	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 700,
		title: 'Import server - Step 1 (Server details)',
		fieldDefaults: {
			msgTarget: 'side',
			anchor: '100%'
		},

		items: [{
			xtype: 'fieldset',
			title: 'Server information',
			labelWidth: 130,
			items: [{
				xtype: 'combo',
				fieldLabel: 'Platform',
				name: 'platform',
				store: moduleParams['platforms'],
				allowBlank: false,
				editable: false,
				value: '',
				itemId: 'platform_combo',
				queryMode: 'local',
				listeners: {
					'change': function() {
						var value = this.getValue();
						if (value == 'eucalyptus' || value == 'rackspace' || value == 'openstack') {
							if (value == 'eucalyptus') {
								var lstore = moduleParams['euca_locations'];
							} else if (value == 'rackspace') {
								var lstore = moduleParams['rs_locations'];
							} else if (value == 'openstack') {
								var lstore = moduleParams['os_locations'];
							}

							form.down('#loc_combo').store.load({ data: lstore });
							form.down('#loc_combo').setValue(form.down('#loc_combo').store.getAt(0).get('id'));
							form.down('#loc_combo').show().enable();
						} else {
							form.down('#loc_combo').hide().disable();
						}
					}
				}
			}, {
				xtype: 'combo',
				fieldLabel: 'Cloud location',
				name: 'cloudLocation',
				store: {
					fields: [ 'id', 'name' ],
					proxy: 'object'
				},
				allowBlank: false,
				valueField: 'id',
				displayField: 'name',
				itemId: 'loc_combo',
				editable: false,
				value: '',
				queryMode: 'local',
				hidden: true
			}, {
				xtype: 'combo',
				fieldLabel: 'Behavior',
				name: 'behavior',
				store: moduleParams['behaviors'],
				allowBlank: false,
				editable: false,
				value: 'base',
				queryMode: 'local'
			}, {
				xtype: 'combo',
				fieldLabel: 'OS type',
				name: 'os',
				store: ['linux', 'windows'],
				allowBlank: false,
				editable: false,
				value: 'linux',
				queryMode: 'local'
			}, {
				xtype: 'textfield',
				name: 'remoteIp',
				fieldLabel: 'Server IP address',
				validator: isValidIPAddress
			}, {
				xtype: 'textfield',
				name: 'roleName',
				fieldLabel: 'Role name',
				value: ''
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
				text: 'Continue',
				width: 80,
				handler: function() {
					if (form.getForm().isValid())
						Scalr.Request({
							processBox: {
								type: 'action',
								msg: 'Initializing import ...'
							},
							form: form.getForm(),
							url: '/servers/xImportStart/',
							success: function (data) {
								Scalr.event.fireEvent('redirect', '#/servers/' + data.serverId + '/importCheck', true);
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
