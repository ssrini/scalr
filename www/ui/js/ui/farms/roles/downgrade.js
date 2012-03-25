Scalr.regPage('Scalr.ui.farms.roles.downgrade', function (loadParams, moduleParams) {
	return Ext.create('Ext.form.Panel', {
		width: 700,
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		title: 'Downgrade farm role to previous version',
		bodyPadding: {
			left: 5,
			right: 5
		},
		fieldDefaults: {
			anchor: '100%',
			labelWidth: 130,
		},
		items: [{
			xtype: 'fieldset',
			title: 'Available roles',
			items: moduleParams['history']
		}],

		dockedItems: [{
			xtype: 'container',
			cls: 'scalr-ui-docked-bottombar',
			dock: 'bottom',
			layout: {
				type: 'hbox',
				pack: 'center'
			},
			items: [{
				xtype: 'button',
				text: 'Downgrade',
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						url: '/farms/' + loadParams['farmId'] + '/roles/' + loadParams['farmRoleId'] + '/xDowngrade',
						form: this.up('form').getForm(),
						success: function () {
							//Scalr.event.fireEvent('close');
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
