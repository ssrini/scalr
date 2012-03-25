Scalr.regPage('Scalr.ui.services.configurations.presets.build', function (loadParams, moduleParams) {
	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 900,
		title: 'Services &raquo; Configurations &raquo; Presets &raquo Create',
		fieldDefaults: {
			msgTarget: 'side'
		},

		items: [{
			xtype: 'fieldset',
			title: 'Preset details',
			labelWidth: 150,
			items:[{
				xtype: 'textfield',
				name: 'presetName',
				fieldLabel: 'Name',
				width: 300,
				value: moduleParams['presetName'],
				readOnly: moduleParams['presetName'] ? true : false
			   }, {
				xtype: 'combo',
				name: 'roleBehavior',
				fieldLabel: 'Service',
				width: 300,
				readOnly: moduleParams['roleBehavior'] ? true : false,
				queryMode: 'local',
				editable: false,
				emptyText: 'Please select service...',
				store: [['mysql','MySQL'], ['app','Apache'], ['memcached','Memcached'], ['cassandra','Cassandra'], ['www','Nginx'], ['redis', ['Redis']]],
				listeners: {
					'select': function() {
						Scalr.Request({
							processBox: {
								type: 'load'
							},
							url: '/services/configurations/presets/xGetPresetOptions',
							params: {
								'compat4': 1,
								'presetId': moduleParams['presetId'],
								'presetName': form.down('[name="presetName"]').getValue(),
								'roleBehavior': form.down('[name="roleBehavior"]').getValue()
							},
							success: function (data) {
								var field = form.down('#optionsSet');

								field.removeAll();
								field.add(data.presetOptions);
								field.show();

								field.items.each(function () {
									var el = this.el.down("img.tipHelp");
									new Ext.ToolTip({
										target: el.id,
										dismissDelay: 0,
										html: this.initialConfig.items[1].hText
									});
								});
							}
						});
					}
				}
			}]
		}, {
			xtype: 'fieldset',
			title: 'Configuration options',
			itemId: 'optionsSet',
			hidden: true,
			items: []
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
						url: '/services/configurations/presets/xSave/',
						params: { 'presetId': moduleParams['presetId'] },
						success: function () {
							Scalr.event.fireEvent('redirect', '#/services/configurations/presets', true);
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

	form.on('afterrender', function(){
		if (moduleParams['roleBehavior']) {
			form.down('[name="roleBehavior"]').setValue(moduleParams['roleBehavior']);
			form.down('[name="roleBehavior"]').fireEvent('select');
		}
	});

	return form;
});
