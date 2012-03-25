Scalr.regPage('Scalr.ui.admin.settings.core', function (loadParams, moduleParams) {
	var form = Ext.create('Ext.form.Panel',{
		bodyCls: 'scalr-ui-frame',
		title: 'Settings &raquo; Core',
		bodyPadding: 5,
		items: [{
			xtype: 'fieldset',
			title: 'Admin account',
			defaults: {
				labelWidth: 300,
				anchor: '100%',
				xtype: 'textfield'
			},
			items: [{
				name: 'email_address',
				fieldLabel: 'E-mail',
				vtype: 'email'
			},{
				name: 'email_name',
				fieldLabel: 'Name'
			}]
		},{
			xtype: 'fieldset',
			title: 'eMail settings',
			items: [{
				xtype: 'container',
				layout: {
					type: 'hbox'
				},
				items: [{
					labelWidth: 300,
					flex: 1,
					xtype: 'textfield',
					name: 'email_dns',
					fieldLabel: 'SMTP connection'
				},{
					xtype: 'displayfield',
					margin: {
						left: 5
					},
					value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
					listeners: {
						'afterrender': function () {
							Ext.create('Ext.tip.ToolTip', {
								target: this.el.down('img.tipHelp'),
								dismissDelay: 0,
								html: 'user:password@host:port. Leave empty to use MTA'
							});
						}
					}
				}]
			},{
				xtype: 'textarea',
				name: 'team_emails',
				fieldLabel: 'Scalr team emails (one per line)',
				labelWidth: 300,
				anchor: '100%'
			}]
		},{
			xtype: 'fieldset',
			title: 'AWS settings',
			defaults: {
				labelWidth: 300,
				anchor: '100%'
			},
			items: [{
				xtype: 'textfield',
				name: 'secgroup_prefix',
				fieldLabel: 'Security groups prefix'
			},{
				xtype: 'textarea',
				name: 's3cfg_template',
				fieldLabel: 'S3cfg template'
			}]
		},{
			xtype: 'fieldset',
			title: 'RRD statistics settings',
			defaults: {
				labelWidth: 300,
				anchor: '100%',
				xtype: 'textfield'
			},
			items: [{
				name: 'rrdtool_path',
				fieldLabel: 'Path to rrdtool binary'
			},{
				name: 'rrd_default_font_path',
				fieldLabel: 'Path to font (for rrdtool)'
			},{
				name: 'rrd_db_dir',
				fieldLabel: 'Path to RRD database dir'
			},{
				xtype: 'container',
				layout: {
					type: 'hbox'
				},
				items: [{
					labelWidth: 300,
					flex: 1,
					xtype: 'textfield',
					name: 'rrd_stats_url',
					fieldLabel: 'Statistics URL'
				},{
					xtype: 'displayfield',
					margin: {
						left: 5
					},
					value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
					listeners: {
						'afterrender': function () {
							Ext.create('Ext.tip.ToolTip', {
								target: this.el.down('img.tipHelp'),
								dismissDelay: 0,
								html: 'Allowed tags: %fid% - Farm ID, %rn% - role name, %wn% - watcher name'
							});
						}
					}
				}]
			},{
				xtype: 'container',
				layout: {
					type: 'hbox'
				},
				items: [{
					labelWidth: 300,
					flex: 1,
					xtype: 'textfield',
					name: 'rrd_graph_storage_path',
					fieldLabel: 'Path to graphics'
				},{
					xtype: 'displayfield',
					margin: {
						left: 5
					},
					value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
					listeners: {
						'afterrender': function () {
							Ext.create('Ext.tip.ToolTip', {
								target: this.el.down('img.tipHelp'),
								dismissDelay: 0,
								html: 'Bucket name for Amazon S3 or path to folder for Local filesystem'
							});
						}
					}
				}]
			}]
		},{
			xtype: 'fieldset',
			title: 'Application settings',
			defaults: {
				labelWidth: 300,
				anchor: '100%',
				xtype: 'textfield'
			},
			items: [{
				xtype: 'container',
				layout: {
					type: 'hbox'
				},
				items: [{
					xtype: 'combo',
					queryMode: 'local',
					store: [['http','http://'],['https','https://']],
					name: 'http_proto',
					fieldLabel: 'Event handler URL',
					labelWidth: 300,
					editable: false
				},{
					flex: 1,
					xtype: 'textfield',
					name: 'eventhandler_url'
				}]
			},{
				name: 'app_sys_ipaddress',
				fieldLabel: 'Server IP address',
				validator: function(value) {
					if (value != ''){
						var reg = /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/;
						if (!reg.test(value)){
							return 'IP adress must be in format 0-255.0-255.0-255.0-255';
						}else {
							var parts = value.split(".");
							if (parseInt(parseFloat(parts[0])) == 0) 
								return 'IP adress must be in format 0-255.0-255.0-255.0-255'; 
							for (var i=0; i < parts.length; i++) {
								if (parseInt(parseFloat(parts[i])) > 255) 
									return 'IP adress must be in format 0-255.0-255.0-255.0-255'; 
							}
						}
					}
					return true;
				}
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
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						url: '/admin/settings/xSave/',
						form: form.getForm(),
						success: function (data) {
						}
					});
				}
			}]
		}]
	});
	if (moduleParams['config'])
		form.getForm().setValues(moduleParams['config']);
		
	return form;
});