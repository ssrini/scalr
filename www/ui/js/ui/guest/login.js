Scalr.regPage('Scalr.ui.guest.login', function (loadParams, moduleParams) {
	return Ext.create('Ext.panel.Panel', {
		width: 400,
		bodyCls: 'scalr-ui-frame',
		title: 'Please login',
		scalrOptions: {
			reload: false
		},
		contentEl: Ext.get('body-login'),
		bodyPadding: 10,
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
					this.up('panel').el.down('input[type="submit"]').dom.click();
				}
			}, {
				xtype: 'button',
				text: 'Forgot password?',
				width: 120,
				margin: {
					left: 5
				},
				handler: function () {
					Scalr.event.fireEvent('redirect', '#/guest/recoverPassword' , true, true, { email: this.up('panel').el.down('input[name=scalrLogin]').getValue() });
				}
			}]
		}],
		listeners: {
			afterrender: function () {
				Ext.get('body-login-container').remove();
				var handler = function () {
					this.down('input[type="submit"]').dom.click();
				};

				new Ext.util.KeyMap(Ext.get('body-login').child('form').down('input[name=scalrLogin]'), {
					key: Ext.EventObject.ENTER,
					fn: handler,
					scope: Ext.get('body-login').child('form')
				});

				new Ext.util.KeyMap(Ext.get('body-login').child('form').down('input[name=scalrPass]'), {
					key: Ext.EventObject.ENTER,
					fn: handler,
					scope: Ext.get('body-login').child('form')
				});

				Ext.get('body-login').child('form').on('submit', function (e) {
					e.preventDefault();

					var form = Ext.get('body-login').child('form'),
						login = form.down('input[name=scalrLogin]').getValue(),
						pass = form.down('input[name=scalrPass]').getValue(),
						keepSession = form.down('input[name=scalrKeepSession]').getValue();

					if (login == '' || pass == '') {
						Scalr.message.Error('Please fill fields');
					} else {
						Scalr.Request({
							processBox: {
								type: 'action'
							},
							scope: this,
							params: {
								scalrLogin: login,
								scalrPass: pass,
								scalrKeepSession: keepSession
							},
							url: '/guest/xLogin',
							success: function (data) {
								if (data.tfa) {
									Scalr.event.fireEvent('redirect', data.tfa, true, true, {
										scalrLogin: login,
										scalrPass: pass,
										scalrKeepSession: keepSession
									});
								} else {
									if (Scalr.user.userId && (data.userId == Scalr.user.userId)) {
										Scalr.user.needLogin = false;
										Scalr.event.fireEvent('close', true, true);
										Scalr.event.fireEvent('unlock');
									} else {
										Scalr.event.fireEvent('close', true, true);
										document.location.reload();
									}
								}
							}
						});
					}
				}, this);
			},
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
