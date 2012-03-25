Scalr.regPage('Scalr.ui.guest.loginTfaGgl', function (loadParams, moduleParams, scalrParams) {
	if (! moduleParams.valid) {
		Scalr.message.Error('Two-factor authentication not enabled for this user');
		Scalr.event.fireEvent('redirect', '#/guest/login', true, true);
	}

	return Ext.create('Ext.form.Panel', {
		title: 'Two-factor authorization',
		width: 350,
		defaults: {
			anchor: '100%',
			labelWidth: 80
		},
		msgTarget: 'side',
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		scalrOptions: {
			'modal': true
		},
		items: [{
			xtype: 'textfield',
			fieldLabel: 'Code',
			name: 'tfaCode',
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
				text: 'Login',
				width: 80,
				handler: function () {
					var me = this;
					if (this.up('form').getForm().isValid()) {
						Scalr.Request({
							processBox: {
								type: 'action'
							},
							form: this.up('form').getForm(),
							url: '/guest/xLoginTfaGgl',
							params: loadParams,
							success: function (data) {
								if (Scalr.user.userId && (data.userId == Scalr.user.userId)) {
									Scalr.user.needLogin = false;
									Scalr.event.fireEvent('close', true, true);
									Scalr.event.fireEvent('unlock');
								} else {
									Scalr.event.fireEvent('close', true, true);
									document.location.reload();
								}
							}
						})
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
		}]
	});
});
