Ext.define('Ext.layout.container.Scalr', {
	extend: 'Ext.layout.container.Absolute',
	alias: [ 'layout.scalr' ],

	activeItem: null,
	zIndex: 101,
	firstRun: true,

	initLayout : function() {
		if (!this.initialized) {
			this.callParent();

			this.owner.on('resize', function () {
				this.onOwnResize();
			}, this);
		}
	},

	setActiveItem: function (newPage, param) {
		var me = this,
			oldPage = this.activeItem;

		if (this.firstRun) {
			Ext.get('loading').down('div').applyStyles('background-position: 0px 0px');
			Ext.get('loading').remove();
			Ext.get('body-container').applyStyles('visibility: visible');
			this.owner.doLayout();

			this.firstRun = false;
		}

		if (newPage) {
			if (oldPage != newPage) {
				if (oldPage) {
					if (oldPage.scalrOptions.modal) {
						if (newPage.scalrOptions.modal) {
							if (
								newPage.rendered &&
								(parseInt(oldPage.el.getStyles('z-index')['z-index'])  == (parseInt(newPage.el.getStyles('z-index')['z-index']) + 1)))
							{
								this.zIndex--;
								oldPage.el.unmask();
								oldPage.destroy();
							} else {
								this.zIndex++;
								oldPage.el.mask();
								oldPage.fireEvent('deactivate');
							}
						} else {
							this.zIndex = 101;
							oldPage.destroy();
							// old window - modal, a new one - no, close all windows with reload = true
							// miss newPage
							if (! newPage.scalrOptions.modal) {
								me.owner.items.each(function () {
									if (this.rendered && !this.hidden && this != newPage) {
										if (this.scalrOptions.reload == true) {
											this.el.unmask();
											this.destroy();
										} else {
											this.el.unmask();
											this.hide();
											this.fireEvent('deactivate');
										}
									}
								});
							}
						}
					} else {
						if (newPage.scalrOptions.modal) {
							oldPage.el.mask();
							oldPage.fireEvent('deactivate');
						} else {
							if (oldPage.scalrOptions.reload) {
								oldPage.el.unmask();
								oldPage.destroy();
							} else {
								oldPage.hide();
								oldPage.fireEvent('deactivate');
							}
						}
					}
				}
			} else {
				if (oldPage.scalrOptions.reload) {
					oldPage.el.unmask();
					oldPage.destroy();
					this.activeItem = null;
					return false;
				}
			}

			this.activeItem = newPage;
			this.setSize(this.activeItem);

			if (! newPage.scalrOptions.modal)
				document.title = ((this.activeItem.title ? (this.activeItem.title + ' - ') : '') + 'Scalr CP').replace(/&raquo;/g, '»');

			if (this.activeItem.scalrReconfigureFlag && this.activeItem.scalrReconfigure)
				this.activeItem.scalrReconfigure(param || {});
			else
				this.activeItem.scalrReconfigureFlag = true;

			this.activeItem.show();
			this.activeItem.el.unmask();
			this.activeItem.fireEvent('activate');

			this.setSizeAfter(this.activeItem);

			if (this.activeItem.scalrOptions.modal)
				this.activeItem.el.setStyle({ 'z-index': this.zIndex });

			return true;
		}
	},

	setSize: function (comp) {
		var r = this.getTarget().getSize();
		var top = 5, left = 0;

		r.height = r.height - top;

		if (comp.scalrOptions.modal) {
			top = top + 5;
			r.height = r.height - 5 * 2;

			if (comp.scalrOptions.maximize == 'all') {
				left = left + 5;
				r.width = r.width - 5 * 2;
			}
		}

		if (comp.scalrOptions.maximize == 'all') {
			comp.setPosition(left, top);
			comp.setSize(r);
		} else {
			if (! Ext.isDefined(comp.widthOriginal)) {
				comp.setWidth(r.width);
				comp.setPosition(left, top);
			} else {
				comp.setWidth(comp.widthOriginal);
				comp.setPosition((r.width - comp.widthOriginal) / 2, top);
			}
		}
	},

	setSizeAfter: function (comp) {
		var r = this.getTarget().getStyleSize();
		var top = 5;

		if (! Ext.isDefined(comp.body))
			return;

		// TODO: clear commented code if it doesn't needed
		if (comp.scalrOptions.maximize == 'all') {
			comp.body.setStyle({
				'max-height': 'none'
			});

			/*if (Ext.isDefined(comp.body.getStyles('overflow-y')['overflow-y']))
				comp.body.setStyle({
					'overflow-y': 'hidden'
				});*/

			/*comp.el.setStyle({
				'overflow': 'hidden',
				'max-height': 'none'
			});*/
		} else {
			var h = 0;

			comp.dockedItems.each(function () {
				h += this.getHeight();
			});

			comp.body.setStyle({
				'max-height': Math.max(0, r.height - 5*2 - 5 - h - comp.el.getPadding('tb') - comp.el.getBorderWidth('tb')) + 'px'
			});

			if (comp.body.getStyle('overflow') == 'hidden')
				comp.body.setStyle({
					'overflow-y': 'auto'
				});

			/*comp.el.setStyle({
				'overflow': 'hidden',
				'max-height': (r.height - top - (comp.scalrOptions.modal ? top * 2 : 0)) + 'px'
			});*/
		}

		comp.doLayout();
	},

	onOwnResize: function () {
		if (this.activeItem) {
			this.setSize(this.activeItem);
			this.setSizeAfter(this.activeItem);
		}
	}
});

Ext.namespace('Scalr.application');
Scalr.application.MainWindow = Ext.create('Ext.panel.Panel', {
	width: '100%',
	height: '100%',
	layout: 'scalr',
	border: 0,
	padding: 5,
	bodyCls: 'x-docked-noborder-top x-docked-noborder-left',
	dockedItems: [/*{
		xtype: 'toolbar',
		dock: 'top',
		height: 28,
		margin: {
			bottom: 5
		},
		cls: 'scalr-mainwindow-docked-warning',
		padding: 5,
		hidden: ! (Scalr.InitParams['user'] ? Scalr.InitParams['user']['userIsOldPkg'] : false),
		html:
			"You're under an old plan that doesn't allow for metered billing. " +
			"If you want to get access to the new features we recently announced, <a href='#/billing/changePlan'>please upgrade your subscription</a>"
	}, */{
		xtype: 'toolbar',
		dock: 'top',
		height: 45,
		margin: {
			bottom: 5
		},
		cls: 'scalr-mainwindow-docked-warning',
		padding: 5,
		hidden: ! (Scalr.InitParams['user'] ? Scalr.InitParams['user']['userIsPaypal'] : false),
		html:
			"Hey mate, I see that you are using Paypal for your subscription. " +
			"Unfortunately paypal hasn't been working too well for us, so we've discontinued its use.<br/>" +
			"<a href='#/billing/changePlan'>Click here to switch to direct CC billing</a>, and have your subscription to paypal canceled."
	}, {
		xtype: 'toolbar',
		dock: 'top',
		itemId: 'top',
		enableOverflow: true,
		height: 34,
		cls: 'scalr-mainwindow-docked-toolbar',
		items: [{
			xtype: 'tbtext',
			text: '<a title="Home" href="/#/dashboard"><img src="/ui/images/icons/scalr_logo_icon_34x26.png"></a>',
			width: 36
		}, Scalr.InitParams.menu || {}],
		listeners: {
			afterrender: function () {
				var e = this.down('[environment="true"]');
				if (e) {
					var handler = function() {
						if (this.envId && Scalr.InitParams.user.envId != this.envId)
							Scalr.Request({
								processBox: {
									type: 'action',
									msg: 'Changing environment ...'
								},
								url: '/core/xChangeEnvironment/',
								params: { envId: this.envId },
								success: function() {
									document.location.reload();
								}
							});
					};

					e.menu.items.each(function(it) {
						it.on('click', handler);
					});

					Scalr.EventMessager.on('update', function (type, env) {
						if (type == 'environments/create') {
							var ind = this.menu.items.indexOf(this.menu.child('menuseparator'));
							this.menu.insert(ind, {
								'text': env.name,
								'checked': false,
								'group': 'environment',
								'envId': env.id
							}).on('click', handler);
						} else if (type == 'environments/delete') {
							var el = this.menu.child('[envId="' + env + '"]');
							if (el) {
								this.menu.remove(el);
							}
						}
					}, e);
				}
			}
		}
	}, {
		xtype: 'panel',
		cls: 'scalr-mainwindow-docked-map',
		border: false,
		dock: 'left',
		hidden: true,
		margin: {
			top: 5,
			right: 5
		},
		itemId: 'map',
		store: Ext.create('Ext.data.TreeStore', {
			root: {
				expanded: true
			}
		}),
		width: 300
	}],
	disabledDockedToolbars: function (disable) {
		Ext.each(this.getDockedItems(), function (item) {
			if (disable)
				item.disable();
			else
				item.enable();
		});
	},
	listeners: {
		afterrender: function () {
			var t = this.body.getTop(true);

			this.elMessages = this.el.createChild({
				tag: 'div',
				id: 'body-container-messages',
				style: 'position: absolute; top: ' + (t + 8 ) + 'px; left: 0px; width: 100%; height: auto'
			});
		},
		add: function (cont, cmp) {
			// hack for grid, dynamic width and columns (afterrender)
			if (cont.el && cmp.scalrOptions && cmp.scalrOptions.maximize == 'all')
				cmp.setWidth(cont.getWidth());
		}
	}
});

Ext.EventManager.onWindowResize(function () {
	var s = Ext.get('body-container').getSize();

	// min - ipad size
	if (s.width < 1000) {
		s.width = 1000;
		Ext.getBody().setStyle('overflow-x', 'scroll');
	} else
		Ext.getBody().setStyle('overflow-x', 'hidden');

	Scalr.application.MainWindow.setSize(s);
});

Scalr.application.MainWindow.add({
	xtype: 'panel',
	width: 400,
	widthOriginal: 400, // TODO: fix
	bodyPadding: 5,
	bodyCls: 'scalr-ui-frame',
	hidden: true,
	itemId: 'loginForm',
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
				Scalr.application.MainWindow.layout.setActiveItem(Scalr.application.MainWindow.getComponent('recoverForm'));
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
								// 2-factor auth
								var f = Scalr.application.MainWindow.add({
									xtype: 'form',
									items: data.tfa,
									title: 'Two-factor authorization',
									width: 350,
									hidden: true,
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
														url: '/guest/xLoginVerify',
														params: {
															scalrLogin: login,
															scalrPass: pass,
															scalrKeepSession: keepSession
														},
														success: function (data) {
															Scalr.application.MainWindow.getComponent('loginForm').hide();
															me.up('form').hide();

															if (Scalr.InitParams['user'] && (data.userId == Scalr.InitParams['user']['userId'])) {
																window.onhashchange();
																//Scalr.message.Info('You have been logged in, but your previous request has not been performed due to lost session error. Please perform it again.');
															} else
																document.location.reload();
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
												Scalr.application.MainWindow.layout.setActiveItem(Scalr.application.MainWindow.getComponent('loginForm'));
											}
										}]
									}],
								});

								Scalr.application.MainWindow.layout.setActiveItem(f);

							} else {
								this.hide();

								if (Scalr.InitParams['user'] && (data.userId == Scalr.InitParams['user']['userId'])) {
									window.onhashchange();
									//Scalr.message.Info('You have been logged in, but your previous request has not been performed due to lost session error. Please perform it again.');
								} else
									document.location.reload();
							}
						}
					});
				}
			}, this);
		},
		show: function () {
			Scalr.application.MainWindow.disabledDockedToolbars();
			Scalr.event.fireEvent('lock');
		},
		hide: function () {
			Scalr.application.MainWindow.disabledDockedToolbars(false);
			Scalr.event.fireEvent('unlock');
		}
	}
});

Scalr.application.MainWindow.add({
	xtype: 'component',
	scalrOptions: {
		'reload': false,
		'maximize': 'all'
	},
	html: '&nbsp;',
	hidden: true,
	title: '',
	itemId: 'blank'
});

Scalr.application.MainWindow.add({
	title: 'Recover password',
	scalrOptions: {
		'reload': false
	},
	bodyPadding: 5,
	bodyCls: 'scalr-ui-frame',
	width: 400,
	widthOriginal: 400, // TODO: fix
	xtype: 'panel',
	layout: 'anchor',
	items: [{
		xtype: 'textfield',
		fieldLabel: 'E-mail',
		labelWidth: 45,
		anchor: '100%',
		vtype: 'email',
		name: 'email',
		msgTarget: 'side',
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
							Scalr.application.MainWindow.layout.setActiveItem(Scalr.application.MainWindow.getComponent('loginForm'));
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
				Scalr.application.MainWindow.layout.setActiveItem(Scalr.application.MainWindow.getComponent('loginForm'));
			}
		}]
	}],
	hidden: true,
	itemId: 'recoverForm',
	listeners: {
		show: function () {
			Scalr.application.MainWindow.disabledDockedToolbars();
			Scalr.event.fireEvent('lock');

			this.down('[name="email"]').setValue(Ext.get('body-login').child('form').down('input[name=scalrLogin]').getValue());
		},
		hide: function () {
			Scalr.application.MainWindow.disabledDockedToolbars(false);
			Scalr.event.fireEvent('unlock');
		}
	}
});

Scalr.application.MainWindow.add({
	title: 'New password',
	scalrOptions: {
		'reload': false
	},
	bodyPadding: 5,
	bodyCls: 'scalr-ui-frame',
	width: 400,
	widthOriginal: 400, // TODO: fix
	xtype: 'panel',
	layout: 'anchor',
	items: [{
		xtype: 'textfield',
		inputType: 'password',
		fieldLabel: 'New password',
		labelWidth: 110,
		anchor: '100%',
		name: 'password',
		msgTarget: 'side',
		allowBlank: false,
		validator: function(value) {
			if (value.length < 6)
				return "Password should be longer than 6 chars";
				
			return true;
		}
	}, {
		xtype: 'textfield',
		fieldLabel: 'Confirm',
		inputType: 'password',
		labelWidth: 110,
		anchor: '100%',
		name: 'password2',
		msgTarget: 'side',
		allowBlank: false,
		validator: function(value) {
			if (value != this.prev('[name="password"]').getValue())
				return "Passwords doesn't match";
				
			return true;
		}
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
			text: 'Update my password',
			width: 140,
			handler: function () {
				if (this.up('panel').down('[name="password"]').validate() && this.up('panel').down('[name="password2"]').validate()) {
					Scalr.Request({
						processBox: {
							type: 'action'
						},
						scope: this.up('panel'),
						params: {
							password: this.up('panel').down('[name="password"]').getValue(),
							confirmHash: Scalr.InitParams['initWindowParams'].confirmHash
						},
						url: '/guest/xConfirmPasswordReset',
						success: function (data) {
							Scalr.EventMessager.fireEvent("redirect", "#/dashboard");
							document.location.reload();
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
				Scalr.application.MainWindow.layout.setActiveItem(Scalr.application.MainWindow.getComponent('loginForm'));
			}
		}]
	}],
	hidden: true,
	itemId: 'newPasswordForm',
	listeners: {
		show: function () {
			Scalr.application.MainWindow.disabledDockedToolbars();
			Scalr.event.fireEvent('lock');
		},
		hide: function () {
			Scalr.application.MainWindow.disabledDockedToolbars(false);
			Scalr.event.fireEvent('unlock');
		}
	}
});

window.onhashchange = function (e) {
	if (Scalr.state.suspendPageForce) {
		Scalr.state.suspendPageForce = false;
	} else {
		if (Scalr.state.suspendPage)
			return;
	}

	Scalr.message.Flush();
	Scalr.message.SetKeepMessages(false);
	Scalr.utils.ClearMsgPanels();

	var h = window.location.hash.substring(1).split('?'), link = '', param = {}, loaded = false;
	if (window.location.hash) {
		// only if hash not null
		if (h[0])
			link = h[0];
		// cut ended /  (/logs/view'/')

		if (h[1])
			param = Ext.urlDecode(h[1]);

	} else {
		document.location.href = "#/dashboard";
	}

	var cacheLink = function (link, cache) {
		var re = cache.replace(/\/\{[^\}]+\}/g, '/([^\\}\\/]+)').replace(/\//g, '\\/'), fieldsRe = /\/\{([^\}]+)\}/g, fields = [];

		while ((elem = fieldsRe.exec(cache)) != null) {
			fields[fields.length] = elem[1];
		}

		return {
			scalrReconfigureFlag: false,
			scalrRegExp: new RegExp('^' + re + '$', 'g'),
			scalrParamFields: fields,
			scalrParamGets: function (link) {
				var pars = {}, reg = new RegExp(this.scalrRegExp), params = reg.exec(link);
				if (Ext.isArray(params))
					params.shift(); // delete first element

				for (var i = 0; i < this.scalrParamFields.length; i++)
					pars[this.scalrParamFields[i]] = Ext.isArray(params) ? params.shift() : '';

				return pars;
			}
		};
	};

	if (link && link != '/') {
		// check in cache
		Scalr.application.MainWindow.items.each(function () {
			if (this.scalrRegExp && this.scalrRegExp.test(link)) {

				//TODO: Investigate in Safari
				this.scalrParamGets(link);

				Ext.apply(param, this.scalrParamGets(link));

				loaded = Scalr.application.MainWindow.layout.setActiveItem(this, param);

				return false;
			}
		});

		if (loaded)
			return;

		var r = {
			disableFlushMessages: true,
			disableAutoHideProcessBox: true,
			url: link,
			params: param,
			success: function (data, response, options) {
				var c = 'Scalr.' + data.moduleName.replace('/ui/js/', '').replace(/-[0-9]+.js/, '').replace(/\//g, '.'), cacheId = response.getResponseHeader('X-Scalr-Cache-Id'), cache = cacheLink(link, cacheId);
				var initComponent = function (c) {
					if (Ext.isObject(c)) {
						c.style = c.style || {};
						Ext.apply(c.style, { position: 'absolute' });
						Ext.apply(c, cache);
						Ext.apply(c, { hidden: true });
						c.scalrOptions = c.scalrOptions || {};
						Ext.applyIf(c.scalrOptions, {
							'reload': true, // close window before show other one
							'modal': false, // mask prev window and show new one
							'maximize': '' // maximize which sides (all, (max-height - default))
						});
						// for layout, save width @TODO better
						if (c.width)
							c.widthOriginal = c.width;

						Scalr.application.MainWindow.add(c);
						Scalr.application.MainWindow.layout.setActiveItem(c, param);
						if (options.processBox)
							options.processBox.close();
					} else {
						if (options.processBox)
							options.processBox.close();

						Scalr.application.MainWindow.layout.setActiveItem(Scalr.application.MainWindow.getComponent('blank'));
					}
				};

				Ext.apply(param, cache.scalrParamGets(link));

				if (Ext.isDefined(Scalr.cache[c]))
					initComponent(Scalr.cache[c](param, data.moduleParams));
				else {
					var head = Ext.getHead();
					if (data.moduleRequiresCss) {
						for (var i = 0; i < data.moduleRequiresCss.length; i++) {
							var el = document.createElement('link');
							el.type = 'text/css';
							el.rel = 'stylesheet';
							el.href = data.moduleRequiresCss[i];

							head.appendChild(el);
						}
					}

					var sc = [ data.moduleName ];
					if (data.moduleRequires)
						sc = sc.concat(data.moduleRequires);

					var load = function () {
						if (sc.length)
							Ext.Loader.injectScriptElement(sc.shift(), load);
						else
							initComponent(Scalr.cache[c](param, data.moduleParams));
					};

					load();
				}
			},
			failure: function (data, response, options) {
				if (options.processBox)
					options.processBox.close();

				Scalr.application.MainWindow.layout.setActiveItem(Scalr.application.MainWindow.getComponent('blank'));
			}
		};

		if (e)
			r['processBox'] = {
				type: 'action',
				msg: 'Loading page. Please wait ...'
			};

		Scalr.Request(r);

		return;
	} else {
		document.location.href = "#/dashboard";
	}
};

Scalr.timeoutHandler = {
	defaultTimeout: 60000,
	timeoutRun: 60000,
	timeoutRequest: 5000,
	params: {},
	enabled: false,
	locked: false,
	clearDom: function () {
		if (Ext.get('body-timeout-mask'))
			Ext.get('body-timeout-mask').remove();

		if (Ext.get('body-timeout-container'))
			Ext.get('body-timeout-container').remove();
	},
	schedule: function () {
		this.timeoutId = Ext.Function.defer(this.run, this.timeoutRun, this);
	},
	createTimer: function (cont) {
		clearInterval(this.timerId);
		var f = Ext.Function.bind(function (cont) {
			var el = cont.child('span');
			if (el) {
				var s = parseInt(el.dom.innerHTML);
				s -= 1;
				if (s < 0)
					s = 0;
				el.update(s.toString());
			} else {
				clearInterval(this.timerId);
			}
		}, this, [ cont ]);

		this.timerId = setInterval(f, 1000);
	},
	undoSchedule: function () {
		clearTimeout(this.timeoutId);
		clearInterval(this.timerId);
	},
	restart: function () {
		this.undoSchedule();
		this.run();
	},
	run: function () {
		Ext.Ajax.request({
			url: '/guest/xPerpetuumMobile',
			params: this.params,
			timeout: this.timeoutRequest,
			scope: this,
			doNotShowError: true,
			callback: function (options, success, response) {
				if (success) {
					try {
						var response = Ext.decode(response.responseText);

						if (response.success != true)
							throw 'False';

						this.clearDom();
						this.timeoutRun = this.defaultTimeout;

						if (! response.isAuthenticated) {
							Scalr.application.MainWindow.layout.setActiveItem(Scalr.application.MainWindow.getComponent('loginForm'));
							this.schedule();
							return;
						} else if (! response.equal) {
							document.location.reload();
							return;
						} else {
							if (this.locked) {
								this.locked = false;
								Scalr.event.fireEvent('unlock');
								// TODO: проверить, нужно ли совместить в unlock
								window.onhashchange(true);
							}

							Scalr.event.fireEvent('update', 'lifeCycle', response);

							this.schedule();
							return;
						}
					} catch (e) {
						this.schedule();
						return;
					}
				}

				if (response.aborted == true) {
					this.schedule();
					return;
				}

				if (response.timedout == true) {
					this.schedule();
					return;
				}

				Scalr.event.fireEvent('lock');
				this.locked = true;

				var mask = Ext.get('body-timeout-mask') || Ext.getBody().createChild({
					id: 'body-timeout-mask',
					tag: 'div',
					style: {
						position: 'absolute',
						top: 0,
						left: 0,
						width: '100%',
						height: '100%',
						background: '#CCC',
						opacity: '0.5',
						'z-index': 300000
					}
				});

				this.timeoutRun += 6000;
				if (this.timeoutRun > 60000)
					this.timeoutRun = 60000;

				if (! Ext.get('body-timeout-container'))
					this.timeoutRun = 5000;

				var cont = Ext.get('body-timeout-container') || Ext.getBody().createChild({
					id: 'body-timeout-container',
					tag: 'div',
					style: {
						position: 'absolute',
						top: '5px',
						left: '5px',
						right: '5px',
						'z-index': 300001,
						background: '#F6CBBA',
						border: '1px solid #BC7D7A',
						'box-shadow': '0 1px #FEECE2 inset',
						font: 'bold 13px arial',
						color: '#420404',
						padding: '10px',
						'text-align': 'center'
					}
				}).applyStyles({ background: '-webkit-gradient(linear, left top, left bottom, from(#FCD9C5), to(#F0BCAC))'
					}).applyStyles({ background: '-moz-linear-gradient(top, #FCD9C5, #F0BCAC)' });

				this.schedule();

				cont.update('Not connected. Connecting in <span>' + this.timeoutRun/1000 + '</span>s. <a href="#">Try now</a> ');
				cont.child('a').on('click', function (e) {
					e.preventDefault();
					cont.update('Not connected. Trying now');
					this.undoSchedule();
					this.run();
				}, this);
				this.createTimer(cont);
			}
		});
	}
};

// Scalr.initParams.user (old)
// Scalr.user (used as global var)
Scalr.Init = function () {
	Scalr.application.MainWindow.render('body-container');
	// init UI or login form
	if (Scalr.InitParams['errorMessage'])
		Scalr.message.Error(Scalr.InitParams['errorMessage']);
	
	if (Ext.isObject(Scalr.InitParams['user'])) {
		Scalr.application.MainWindow.getComponent('map').store.load({ data: Scalr.InitParams['farms'] || [] });
		if (Scalr.InitParams.flags.needEnvConfig && !sessionStorage.getItem('needEnvConfigLater')) {
			Scalr.flags.needEnvConfig = true;
			Scalr.event.fireEvent('lock');
			Scalr.event.fireEvent('redirect', '#/environments/' + Scalr.InitParams.flags.needEnvConfig + '/platform/ec2', true, true);
			Scalr.event.on('update', function (type, platform, enabled) {
				if (! sessionStorage.getItem('needEnvConfigDone')) {
					if (type ==  '/environments/' + Scalr.InitParams.flags.needEnvConfig + '/edit') {
						if (enabled) {
							sessionStorage.setItem('needEnvConfigDone', true);
							Scalr.event.fireEvent('unlock');
							Scalr.flags.needEnvConfig = false;
							if (platform == 'ec2') {
								Scalr.message.Success('Cloud credentials successfully configured. Now you can start to build your first farm. <a target="_blank" href="http://www.youtube.com/watch?v=6u9M-PD-_Ds&t=6s">Learn how to do this by watching video tutorial.</a>');
								Scalr.event.fireEvent('redirect', '#/farms/build', true);
							} else {
								Scalr.message.Success('Cloud credentials successfully configured. You need to create some roles before you will be able to create your first farm.');
								Scalr.event.fireEvent('redirect', '#/roles/builder', true);
							}
						}
					}
				}
			});
		}
		window.onhashchange(false);
	} else {
		Scalr.application.MainWindow.layout.setActiveItem(Scalr.application.MainWindow.getComponent(Scalr.InitParams['initWindow'] || 'loginForm'));
	}

	new Ext.util.KeyMap(Ext.getBody(), [{
		key: Ext.EventObject.ESC,
		fn: function () {
			if (Scalr.flags.suspendPage == false && Scalr.application.MainWindow.layout.activeItem.scalrOptions.modal == true) {
				Scalr.EventMessager.fireEvent('close');
			}
		}
	}]);

	if (Ext.isObject(Scalr.InitParams['user'])) {
		Scalr.timeoutHandler.enabled = true;
		Scalr.timeoutHandler.params = Scalr.InitParams['user'];
		Scalr.timeoutHandler.schedule();
	}

	window.onunload = function () {
		Scalr.timeoutHandler.enabled = false;
		Scalr.timeoutHandler.undoSchedule();
		Scalr.timeoutHandler.clearDom();

		Ext.getBody().createChild({
			tag: 'div',
			style: {
				opacity: '0.8',
				background: '#EEE',
				'z-index': 400000,
				position: 'absolute',
				top: 0,
				left: 0,
				width: '100%',
				height: '100%'
			}
		});
	};

	/*window.onbeforeunload = function (e) {
		var message = "Where are you gone?";
		e = e || window.event;

		if (e)
			e.returnValue = message;

		return message;
	};*/

	window.onerror = function (message, source, lineno) {
		Scalr.message.Error(message);
		return false;
	};
};
