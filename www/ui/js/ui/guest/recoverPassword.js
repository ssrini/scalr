Scalr.regPage('Scalr.ui.guest.recoverPassword', function (loadParams, moduleParams) {
	return Ext.create('Ext.panel.Panel', {
		title: 'Recover password',
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 400,
		layout: 'anchor',
		items: [{
			xtype: 'textfield',
			fieldLabel: 'E-mail',
			labelWidth: 45,
			anchor: '100%',
			vtype: 'email',
			name: 'email',
			msgTarget: 'side',
			value: loadParams['email'] || '',
			allowBlank: false
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
				text: 'Send me new password',
				width: 140,
				handler: function () {
					if (this.up('panel').down('[name="email"]').validate()) {
						Scalr.Request({
							processBox: {
								type: 'action'
							},
							scope: this.up('panel'),
							params: {
								email: this.up('panel').down('[name="email"]').getValue()
							},
							url: '/guest/xResetPassword',
							success: function (data) {
								Scalr.event.fireEvent('redirect', '#/guest/login', true, true);
							}
						});
					}
				}
			}, {
				xtype: 'button',
				text: 'Cancel',
				width: 80,
				margin: {
					left: 5
				},
				handler: function () {
					Scalr.event.fireEvent('redirect', '#/guest/login', true, true);
				}
			}]
		}],
		hidden: true,
		itemId: 'recoverForm',
		listeners: {
			activate: function () {
				if (Scalr.user.userId && !Scalr.user.needLogin) {
					Scalr.event.fireEvent('close');
				} else {
					Scalr.event.fireEvent('lock');
				}
			},
			deactivate: function () {
				Scalr.event.fireEvent('unlock');
			}
		}
	});
});
