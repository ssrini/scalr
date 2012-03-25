Scalr.regPage('Scalr.ui.core.api', function (loadParams, moduleParams) {
	var params = moduleParams;
	
	return Ext.create('Ext.form.Panel', {
		width: 700,
		frame: true,
		title: 'API access details & settings',
		bodyPadding: {
			left: 5,
			right: 5
		},
		items: [{
			xtype: 'fieldset',
			title: 'Enable API for current environment',
			checkboxToggle:  true,
			collapsed: !params['api.enabled'],
			checkboxName: 'api.enabled',
			inputValue: 1,
			items: [{
				xtype: 'textfield',
				name: 'api.access_key',
				fieldLabel: 'API Key ID',
				readOnly: true,
				value: params['api.access_key'],
				anchor:'-20'
			}, {
				xtype: 'textarea',
				name: 'api.secret_key',
				fieldLabel: 'API Access Key',
				readOnly: true,
				height: 100,
				value: params['api.secret_key'],
				anchor:'-20'
			}, {
				xtype:'displayfield',
				value:'<br />API access whitelist (by IP address)<br />Example: 67.45.3.7, 67.46.*.*, 91.*.*.*'
			}, {
				xtype:'textarea',
				hideLabel: true,
				name:'api.ip.whitelist',
				height: 100,
				value: params['api.ip.whitelist'],
				anchor:'-20'
			}]
		}],

		dockedItems: [{
			xtype: 'container',
			dock: 'bottom',
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
						url: '/core/xSaveApiSettings/',
						form: this.up('form').getForm(),
						success: function () {
							
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
