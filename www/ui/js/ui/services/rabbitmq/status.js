Scalr.regPage('Scalr.ui.services.rabbitmq.status', function (loadParams, moduleParams) {
	var panel = Ext.create('Ext.form.Panel', {
		width: 700,
		title: 'RabbitMQ status',
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		fieldDefaults: {
			anchor: '100%',
			labelWidth: 130
		},
		items: [{
			xtype: 'fieldset',
			title: 'RabbitMQ access credentials',
			items: [{
				xtype: 'displayfield',
				fieldLabel: 'Login',
				hidden: !moduleParams['rabbitmq']['password'],
				value: 'scalr'
			}, {
				xtype: 'displayfield',
				fieldLabel: 'Password',
				hidden: !moduleParams['rabbitmq']['password'],
				value: moduleParams['rabbitmq']['password']
			}, {
				xtype: 'fieldcontainer',
				fieldLabel: 'Control panel',
				hideLabel: !moduleParams['rabbitmq']['showStatusLabel'],
				layout: {
					type: 'hbox'
				},
				items: [{
					xtype: 'displayfield',
					hidden: moduleParams['rabbitmq']['url'] ? false : true,
					margin: {
						right: 3
					},
					value: '<a href="' + moduleParams['rabbitmq']['url'] + '" target="_blank">' + moduleParams['rabbitmq']['url'] + '</a>'
				}, {
					xtype: 'button',
					hidden: !moduleParams['rabbitmq']['showSetup'],
					margin: {
						right: 3
					},
					name: 'setupCP',
					text: 'Setup Control panel',
					handler: function(){
						Scalr.Request({
							processBox: {
								type: 'action',
								msg: 'Please wait...'
							},
							url: '/services/rabbitmq/xSetupCp/',
							params: {
								farmId: moduleParams['farmId']
							},
							success: function(data) {
								panel.down('[name="status"]').show().setValue(data.status);
								panel.down('[name="setupCP"]').hide();
							}
						});
					}
				}, {
					xtype: 'displayfield',
					name: 'status',
					hidden: moduleParams['rabbitmq']['status'] ? false : true,
					value: moduleParams['rabbitmq']['status']
				}]
			}]
		}]
	});

	if (moduleParams['rabbitmq']['overview']) {
		var overview = moduleParams['rabbitmq']['overview'];
		panel.add({
			xtype: 'fieldset',
			title: 'Overview',
			defaults: {
				labelWidth: 160
			},
			items: [{
				xtype: 'displayfield',
				fieldLabel: 'Management version',
				value: overview['management_version']
			}, {
				xtype: 'displayfield',
				fieldLabel: 'Ready queued messages',
				value: overview['queue_totals']['messages_ready']
			}, {
				xtype: 'displayfield',
				fieldLabel: 'Unacknowledged queued messages',
				value: overview['queue_totals']['messages_unacknowledged']
			}, {
				xtype: 'displayfield',
				fieldLabel: 'Total queued messages',
				value: overview['queue_totals']['messages']
			}]
		});
	}

	return panel;
});
