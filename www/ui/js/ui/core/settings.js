Scalr.regPage('Scalr.ui.core.settings', function (loadParams, moduleParams) {
	return Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 700,
		title: 'System settings',
		items: [{
			xtype: 'fieldset',
			title: 'RSS feed settings',
			items: [{
				cls: 'scalr-ui-form-field-info',
				html: 'Each farm has an events and notifications page. You can get these events outside of Scalr on an RSS reader with the below credentials.',
				border: false
			}, {
				xtype: 'textfield',
				name: 'rss_login',
				width: 200,
				fieldLabel: 'Login',
				value: moduleParams['rss_login'] ? moduleParams['rss_login'] : ''
			}, {
				xtype: 'fieldcontainer',
				fieldLabel: 'Password',
				layout: 'hbox',
				items: [{
					xtype: 'textfield',
					name: 'rss_pass',
					width: 200,
					hideLabel: true,
					itemId: 'rss_pass',
					value: moduleParams['rss_pass'] ? moduleParams['rss_pass'] : '' 
				}, {
					xtype: 'button',
					text: 'Generate',
					width: 80,
					margin: {
						left: 5
					},
					handler: function() {
						function getRandomNum() {
							var rndNum = Math.random()
							rndNum = parseInt(rndNum * 1000);
							rndNum = (rndNum % 94) + 33;
							return rndNum;
						};

						function checkPunc(num) {
							if ((num >=33) && (num <=47)) { return true; }
							if ((num >=58) && (num <=64)) { return true; }
							if ((num >=91) && (num <=96)) { return true; }
							if ((num >=123) && (num <=126)) { return true; }
							return false;
						};

						var length=16;
						var sPassword = "";

						for (i=0; i < length; i++) {
							numI = getRandomNum();
							while (checkPunc(numI)) { numI = getRandomNum(); }
							sPassword = sPassword + String.fromCharCode(numI);
						}

						this.up('form').query('#rss_pass')[0].setValue(sPassword);
					}
				}]
			}]
		}, {
			xtype: 'fieldset',
			title: 'UI settings',
			items: [{
				xtype: 'button',
				text: 'Reset UI settings to defaults',
				handler: function () {
					localStorage.clear();
					Scalr.message.Success('Settings successfully reset');
				}
			}]
		}, {
			xtype: 'fieldset',
			title: 'Environment settings',
			items: [{
				xtype: 'combo',
				store: {
					fields: [ 'id', 'name' ],
					proxy: 'object',
					data: moduleParams['default_environment_list']
				},
				valueField: 'id',
				displayField: 'name',
				value: moduleParams['default_environment'],
				fieldLabel: 'Default',
				queryMode: 'local',
				editable: false,
				width: 400,
				name: 'default_environment'
			}]
		}, {
			xtype: 'fieldset',
			hidden: !moduleParams['settings']['security_2fa'],
			title: 'Two-factor authentication based on <a href="http://code.google.com/p/google-authenticator/" target="_blank">google authenticator</a>',
			items: [{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				itemId: 'enabled',
				hidden: moduleParams['settings']['security_2fa_ggl'] == '1' ? false : true,
				items: [{
					xtype: 'displayfield',
					value: '<span style="color: green">Enabled</span>'
				}, {
					xtype: 'button',
					margin: {
						left: 3
					},
					text: '<span style="color: red">Disable</span>',
					handler: function () {
						Scalr.Request({
							processBox: {
								type: 'action'
							},
							confirmBox: {
								type: 'action',
								msg: 'Are you sure to want to disable two-factor authentication?'
							},
							url: '/core/xSettingsDisable2FaGgl/',
							success: function () {
								this.up().hide().next().show();
							},
							scope: this
						});
					}
				}]
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				itemId: 'disabled',
				hidden: moduleParams['settings']['security_2fa_ggl'] == '1' ? true : false,
				items: [{
					xtype: 'displayfield',
					value: '<span style="color: red">Disabled</span>'
				}, {
					xtype: 'button',
					margin: {
						left: 3
					},
					text: '<span style="color: green">Enable</span>',
					handler: function () {
						var b32 = ('234567QWERTYUIOPASDFGHJKLZXCVBNM').split(''), barcode = '';

						for (var i = 0; i < 16; i++)
							barcode = barcode + b32[Math.floor(Math.random() * (b32.length))];

						this.up().next('[name="security_2fa_ggl_key"]').show().setValue(barcode);
						this.up().next('[name="security_2fa_ggl_qr"]').show().setValue(
							'<img src="http://chart.apis.google.com/chart?cht=qr&chs=200x200&chld=H|0&chl=otpauth://totp/scalr:' +
							moduleParams['user_email']  +
							'?secret=' + barcode + '">'
						);
						this.up().next('[name="security_2fa_ggl_code"]').show();
					}
				}]
			}, {
				xtype: 'textfield',
				readOnly: true,
				name: 'security_2fa_ggl_key',
				fieldLabel: 'Key',
				width: 400,
				hidden: true
			}, {
				xtype: 'displayfield',
				hideLabel: true,
				padding: {
					left: 105
				},
				margin: {
					bottom: 10
				},
				hidden: true,
				name: 'security_2fa_ggl_qr',
				height: 200
			}, {
				xtype: 'fieldcontainer',
				name: 'security_2fa_ggl_code',
				hidden: true,
				anchor: '100%',
				fieldLabel: 'Validate code',
				layout: {
					type: 'hbox'
				},
				items: [{
					xtype: 'textfield',
					width: 60,
					allowBlank: false
				}, {
					xtype: 'button',
					margin: {
						left: 3
					},
					width: 120,
					text: 'Validate and enable',
					handler: function () {
						if (this.prev().isValid())
							Scalr.Request({
								processBox: {
									type: 'action'
								},
								url: '/core/xSettingsEnable2FaGgl/',
								params: {
									qr: this.up().prev('[name="security_2fa_ggl_key"]').getValue(),
									code: this.prev().getValue()
								},
								success: function () {
									this.up().prev('[name="security_2fa_ggl_key"]').hide();
									this.up().prev('[name="security_2fa_ggl_qr"]').hide();
									this.up().hide();

									this.up().up().child('#disabled').hide();
									this.up().up().child('#enabled').show();
								},
								scope: this
							});
					}
				}]
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
				itemId: 'buttonSubmit',
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						url: '/core/xSaveSettings/',
						form: this.up('form').getForm(),
						success: function () {
							Scalr.event.fireEvent('close');
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
