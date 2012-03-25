Ext.ns('Scalr.utils');

Ext.get('body-message-container-mask').dom.count = 0;

Scalr.utils.panels = [];
Scalr.utils.CreateMsgPanel = function(config) {
	var panel = Ext.create('Ext.panel.Panel', Ext.apply({
		renderTo: 'body-message-container',
		itemId: 'panel',
		bodyStyle: 'background-color: #DFE9F6; border-color: #aaa; width: auto',
		style: 'box-shadow: 0px 0px 5px 0 #aaa;',
		autoShow: true,
		listeners: {
			afterrender: function () {
				var maskSize = Ext.getBody().getStyleSize();
				Ext.get('body-message-container').setLeft(Math.ceil((maskSize.width - this.width) / 2));
				Ext.get('body-message-container').setTop(165);
				Ext.get('body-message-container').setStyle('z-index', this.zIndex + 1);

				Ext.get('body-message-container-mask').setDisplayed(true);
				Ext.get('body-message-container-mask').dom.count++;
				Ext.get('body-message-container-mask').setStyle('z-index', this.zIndex);
			},
			close: function () {
				Scalr.utils.panels.pop();
				Ext.get('body-message-container-mask').dom.count--;
				if (! Ext.get('body-message-container-mask').dom.count)
					Ext.get('body-message-container-mask').setDisplayed(false);
			}
		}
	}, config));

	Scalr.utils.panels.push(panel);
	return panel;
};

Scalr.utils.ClearMsgPanels = function () {
	Ext.each(Scalr.utils.panels, function (item) {
		item.close();
	});
};

Scalr.utils.CreateProcessBox = function (config) {
	config['icon'] = 'scalr-mb-icon-' + config['type'];
	var a = '';
	switch (config['type']) {
		case 'delete':
			a = 'Deleting ... Please wait ...'; break;
		case 'reboot':
			a = 'Rebooting ...'; break;
		case 'terminate':
			a = 'Terminating ...'; break;
		case 'launch':
			a = 'Launching ...'; break;
		case 'save':
			a = 'Saving ...'; break;
		case 'action': default:
			a = 'Processing ... Please wait ...'; config['icon'] = 'scalr-mb-icon-action'; break;
	};

	config = Ext.applyIf(config, { msg: a });

	return Scalr.utils.CreateMsgPanel({
		width: 422,
		zIndex: 21000,
		items: [{
			margin: '20 20 10 20',
			border: false,
			bodyCls: config['icon'] + ' scalr-mb-icon-text',
			html: config['msg']
		}, {
			border: false,
			height: 20,
			bodyStyle: 'background-color: inherit;',
			margin: '10 20 15 20',
			html: '<img src="/ui/images/icons/anim/progress.gif" />'
		}]
	});
};

Scalr.utils.CreateProcessComponent = function (config) {
	config = Ext.applyIf(config, {
		msg: 'Please wait while loading',
		msgCls: '',
		element: ''
	});

	if (config.component.rendered)
		config.component.el.mask(config.msg, config.msgCls);

	return {
		close: Ext.Function.bind(function () {
			if (this.component.rendered)
				this.component.el.unmask();
		}, config)
	};
};

Scalr.utils.CloneObject = function (o) {
	if (o == null || typeof(o) != 'object')
		return o;

	if(o.constructor == Array)
		return [].concat(o);

	var t = {};
	for (var i in o)
		t[i] = Scalr.utils.CloneObject(o[i]);

	return t;
};

Scalr.utils.Confirm = function (config) {
	config['icon'] = 'scalr-mb-icon-' + config['type'];
	var a = '';
	switch (config['type']) {
		case 'delete':
			a = 'Delete'; break;
		case 'reboot':
			a = 'Reboot'; break;
		case 'terminate':
			a = 'Terminate'; break;
		case 'launch':
			a = 'Launch'; break;
	};

	if (config.objects) {
		config.objects.sort();
		var r = '<span style="font-weight: 700;">' + config.objects.shift() + '</span>';
		if (config.objects.length)
			r = r + ' and <span title="' + config.objects.join("\n") + '" style="font-weight: 700; border-bottom: 1px dashed #000080;">' + config.objects.length + ' others</span>';

		config.msg = config.msg.replace('%s', r);
	}

	config['ok'] = config['ok'] || a;
	config['closeOnSuccess'] = config['closeOnSuccess'] || false;
	var items = [];

	if (Ext.isDefined(config.type)) {
		items.push({
			margin: 10,
			border: false,
			bodyCls: config['icon'] + ' scalr-mb-icon-text',
			html: config['msg']
		});
	}

	if (Ext.isDefined(config.form)) {
		var form = {
			margin: {
				left: 5,
				right: 5,
				top: Ext.isDefined(config.type) ? 0 : 5
			},
			bodyStyle: {
				'background-color': 'inherit'
			},
			layout: 'anchor',
			itemId: 'form',
			xtype: 'form',
			border: false,
			defaults: {
				msgTarget: 'side',
				anchor: '100%'
			},
			items: config.form
		};

		if (Ext.isDefined(config.formValidate)) {
			form.listeners = {
				validitychange: function (form, valid) {
					if (valid)
						this.up('#panel').down('#buttonOk').enable();
					else
						this.up('#panel').down('#buttonOk').disable();
				},
				afterrender: function () {
					if (this.form.hasInvalidField())
						this.up('#panel').down('#buttonOk').disable();
				}
			};
		}

		items.push(form);
	}

	var win = Scalr.utils.CreateMsgPanel({
		width: config.formWidth || 400,
		title: config.title || 'Confirmation',
		items: items,
		zIndex: 18000,
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
				text: config['ok'] || 'OK',
				width: 80,
				itemId: 'buttonOk',
				disabled: config['disabled'] || false,
				handler: function () {
					var values = this.up('#panel').down('#form') ? this.up('#panel').down('#form').getValues() : {};

					if (! config.closeOnSuccess)
						this.up('#panel').close();

					if (config.success.call(config.scope || this.up('#panel'), values, this.up('#panel') ? this.up('#panel').down('#form') : this) && config.closeOnSuccess) {
						this.up('#panel').close();
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
					this.up('#panel').close();
				}
			}]
		}]
	});

	win.keyMap = new Ext.util.KeyMap(Ext.getBody(), [{
		key: Ext.EventObject.ESC,
		fn: function () {
			this.close();
		},
		scope: win
	}]);

	if (! Ext.isDefined(config.form)) {
		win.keyMap.addBinding({
			key: Ext.EventObject.ENTER,
			fn: function () {
				var btn = this.down('#buttonOk');
				btn.handler.call(btn);
			},
			scope: win
		});
	}

	win.on('destroy', function () {
		this.keyMap.destroy();
	});

	return win;
};

Scalr.utils.Request = function (config) {
	var currentUrl = document.location.href;

	config = Ext.apply(config, {
		callback: function (options, success, response) {
			if (!options.disableAutoHideProcessBox && options.processBox)
				options.processBox.close();

			if (options.processComponent)
				options.processComponent.close();

			if (success == true && (Ext.isDefined(response.status) ? response.status == 200 : true))  {
				// only for HTTP Code = 200 (for fake ajax upload files doesn't exist response status)
				try {
					var result = Ext.decode(response.responseText);

					if (result.success == true) {
						if (result.successMessage)
							Scalr.message.Success(result.successMessage);

						if (result.warningMessage)
							Scalr.message.Warning(result.warningMessage);

						try {
							options.successF.call(this, result, response, options);
						} catch (e) {
							Scalr.message.Error('Success handler error:' + e);
						}
						return true;
					} else {
						if (result.errorMessage)
							Scalr.message.Error(result.errorMessage);

						try {
							options.failureF.call(this, result, response, options);
						} catch (e) {
							Scalr.message.Error('Failure handler error:' + e);
						}
						return;

						// TODO: check !!!!
						/*if (! Ext.isObject(options.form))
							Scalr.message.Error('Cannot proceed your request at the moment. Please try again later.', true);*/
					}
				} catch (e) {
					Scalr.message.Error('Received incorrect response from server (' + e + ')');
					//Scalr.utils.PostReport(response, options, e);
				}
			}
			// else nothing, global error handler used (if status code != 200)

			options.failureF.call(this, null, response, options);
		}
	});

	config.disableFlushMessages = !!config.disableFlushMessages;
	if (! config.disableFlushMessages)
		Scalr.message.Flush();

	config.disableAutoHideProcessBox = !!config.disableAutoHideProcessBox;

	config.successF = config.success || function () {};
	config.failureF = config.failure || function () {};
	config.scope = config.scope || config;
	config.params = config.params || {};

	delete config.success;
	delete config.failure;

	var pf = function (config) {
		if (config.processBox) {
			config.processBox = Scalr.utils.CreateProcessBox(config.processBox);
		}

		if (config.processComponent) {
			config.processComponent = Scalr.utils.CreateProcessComponent(config.processComponent);
		}

		if (config.form) {
			config['success'] = function (form, action) {
				action.callback.call(this, action, true, action.response);
			};

			config['failure'] = function (form, action) {
				// investigate later, in extjs 4
				action.callback.call(this, action, /*(action.response.status == 200) ? true : false*/ true, action.response);
			};
			config['clientValidation'] = false;

			if (config.form.hasUpload()) {
				config.params['X-Requested-With'] = 'XMLHttpRequest';
			}

			config.form.submit(config);
		} else {
			return Ext.Ajax.request(config);
		}
	};

	if (Ext.isObject(config.confirmBox)) {
		config.confirmBox['success'] = function (params) {
			delete config.confirmBox;

			if (Ext.isDefined(params))
				Ext.applyIf(config.params, params);

			pf(config);
		};

		Scalr.Confirm(config.confirmBox);
	} else {
		return pf(config);
	}
};

Scalr.utils.UserLoadFile = function (path) {
	Ext.Function.defer(
		Ext.getBody().createChild({
			tag: 'iframe',
			src: path,
			width: 0,
			height: 0,
			frameborder: 0
		}).remove, 1000
	);
};

Scalr.utils.PostReport = function (response, options, exception) {
	Scalr.message.Info('Trying to send bug report', true);
	Ext.Ajax.request({
		url: '/core/xPostDebug',
		params: {
			url: document.location.href,
			request: Ext.encode({ url: options.url, params: options.params }),
			response: Ext.encode([ response.getAllResponseHeaders(), response.responseText ]),
			exception: Ext.encode(exception)
		},
		success: function (response) {
			try {
				var result = Ext.decode(response.responseText);
				if (result.success == true)
					Scalr.message.Info('Bug report successfully sent [reportId: ' + result.reportId + ']', true);
				else
					Scalr.message.Error('Error sending bug report (' + result.errorMessage + ')', true);
			} catch (e) {
				Scalr.message.Error('Error sending bug report (' + e + ')', true);
			}
		},
		failure: function (response) {
			Scalr.message.Error('Error sending bug report', true);
		}
	});
};

Scalr.utils.IsEqualValues = function (obj1, obj2) {
	for (var i in obj1) {
		if (! Ext.isDefined(obj2[i]) && obj1[i] == obj2[i])
			return false;
	}

	return true;
}

// shorter name
Scalr.Confirm = Scalr.utils.Confirm;
Scalr.Request = Scalr.utils.Request;
