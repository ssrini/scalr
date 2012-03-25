Scalr.regPage('Scalr.ui.roles.builder', function (loadParams, moduleParams) {
	var result = { behaviors: [], addons: [ 'chef' ] };

	if (! Ext.isObject(moduleParams.platforms)) {
		Scalr.message.Error('You need to enable at least one cloud platform');
		Scalr.event.fireEvent('redirect', moduleParams['environment'], true);
		return false;
	}

	var platforms = [];
	for (var i in moduleParams.platforms)
		platforms[platforms.length] = {
			xtype: 'custombutton',
			width: 109,
			height: 109,
			allowDepress: false,
			toggleGroup: 'scalr-ui-roles-builder-os',

			renderTpl:
				'<div class="scalr-ui-btn-custom" id="{id}-btnEl">' +
					'<div class="{prefix}-btn-icon"><img src="{icon}"></div>' +
					'<div class="{prefix}-btn-name">{name}</div>' +
				'</div>',
			renderData: {
				prefix: 'scalr-ui-roles-builder',
				name: moduleParams.platforms[i],
				icon: '/ui/images/icons/platform/' + i + '_64x64.png'
			},
			platform: i,
			margin: 10,
			toggleHandler: function () {
				if (this.pressed) {
					result['platform'] = this.platform;
					form.down("#step1").setTitle('Step 1 - Choose platform [' + this.renderData.name + ']');
					form.stepNext();
				} else {
					form.stepChanged();
				}
			}
		};

	var checkboxBehaviorListener = function() {
		if (this.behavior == 'mysql') {
			if (this.pressed)
				form.down('#softwareSet').show();
			else
				form.down('#softwareSet').hide();
		}

		if (this.behavior == 'app') {
			if (this.pressed)
				form.down('[behavior="www"]').disable();
			else
				form.down('[behavior="www"]').enable();
		}

		if (this.behavior == 'mysql') {
			if (this.pressed) {
				form.down('[behavior="postgresql"]').disable();
				form.down('[behavior="redis"]').disable();
				form.down('[behavior="mongodb"]').disable();
			}
			else {
				form.down('[behavior="postgresql"]').enable();	
				form.down('[behavior="redis"]').enable();
				form.down('[behavior="mongodb"]').enable();
			}
		}

		if (this.behavior == 'redis') {
			if (this.pressed) {
				form.down('[behavior="mysql"]').disable();
				form.down('[behavior="postgresql"]').disable();
				form.down('[behavior="mongodb"]').disable();
			}
			else {
				form.down('[behavior="mysql"]').enable();
				form.down('[behavior="postgresql"]').enable();
				form.down('[behavior="mongodb"]').enable();
			}
		}

		if (this.behavior == 'mongodb') {
			if (this.pressed) {
				form.down('[behavior="postgresql"]').disable();
				form.down('[behavior="mysql"]').disable();
				form.down('[behavior="redis"]').disable();
			}
			else {
				form.down('[behavior="mysql"]').enable();
				form.down('[behavior="redis"]').enable();
				form.down('[behavior="postgresql"]').enable();
			}
		}

		if (this.behavior == 'postgresql') {
			if (this.pressed) {
				form.down('[behavior="mongodb"]').disable();
				form.down('[behavior="mysql"]').disable();
				form.down('[behavior="redis"]').disable();
			}
			else {
				form.down('[behavior="mysql"]').enable();
				form.down('[behavior="redis"]').enable();
				form.down('[behavior="mongodb"]').enable();
			}
		}

		if (this.behavior == 'www') {
			if (this.pressed)
				form.down('[behavior="app"]').disable();
			else
				form.down('[behavior="app"]').enable();
		}

		if (this.pressed) {
			result.behaviors.push(this.behavior);
		} else {
			Ext.Array.remove(result.behaviors, this.behavior);
		}

		if (result.behaviors.length > 1) {
			form.down('#settings-group').setValue('Mixed images');
		} else if (result.behaviors.length) {
			var ar = {
				'app': 'Application servers',
				'mysql': 'Database servers',
				'postgresql': 'Database servers',
				'www': 'Load balancers',
				'memcached': 'Caching servers',
				'redis': 'Database servers',
				'mongodb': 'Database servers',
				'rabbitmq': 'MQ servers'
			};

			form.down('#settings-group').setValue(ar[result.behaviors[0]]);
		} else {
			form.down('#settings-group').setValue('Base images');
		}
	};

	var checkboxAddonsListener = function() {
		if (this.pressed) {
			result.addons.push(this.behavior);
		} else {
			Ext.Array.remove(result.addons, this.behavior);
		}
	}

	var form = Ext.create('Ext.panel.Panel', {
		title: 'Roles builder',
		width: 700,
		height: 730,
		layout: 'accordion',
		layoutConfig: {
			hideCollapseTool: true
		},

		defaults: {
			border: false,
			bodyCls: 'scalr-ui-frame',
			toggleCollapse: function (animate) {
				if (this.collapsed) {
					this.ownerCt.layout.expandedItem = this.ownerCt.items.indexOf(this);
					this.expand(animate);
				}
				return this;
			}
		},
		stepChanged: function () {
			var i = this.layout.expandedItem;
			for (var len = this.items.length, i = i + 1; i < len; i++)
				this.items.get(i).disable();
		},
		stepNext: function (comp) {
			var i = this.layout.expandedItem + 1;
			if (i < this.items.length) {
				this.items.get(i).enable();
				this.items.get(i).expand();
				this.layout.expandedItem = i;
			}
		},

		items: [{
			title: 'Step 1 - Choose platform',
			layout: 'column',
			hideCollapseTool: true,
			itemId: 'step1',
			items: platforms
		}, {
			title: 'Step 2 - Choose OS',
			disabled: true,
			hideCollapseTool: true,
			itemId: 'step2',
			layout: 'anchor',
			items: [{
				xtype: 'fieldset',
				style: 'margin: 10px',
				title: 'Choose location and architecture',
				layout: 'column',
				items: [{
					xtype: 'combo',
					allowBlank: false,
					editable: false,
					queryMode: 'local',

					fieldLabel: 'Location',
					columnWidth: .50,
					itemId: 'location',
					valueField: 'name',
					displayField: 'name',
					store: {
						fields: [ 'name' ],
						proxy: 'object'
					},
					listeners: {
						change: function () {
							result['location'] = this.getValue();
							form.down('#step2').applyFilter();
						}
					}
				}, {
					xtype: 'container',
					columnWidth: .15,
					html: '&nbsp;'
				}, {
					xtype: 'radiogroup',
					itemId: 'architecture',
					columnWidth: .20,
					layout: 'anchor',
					items: [{
						boxLabel: 'i386',
						name: 'architecture',
						inputValue: 'i386'
					}, {
						boxLabel: 'x86_64',
						name: 'architecture',
						inputValue: 'x86_64'
					}],
					listeners: {
						change: function () {
							if (Ext.isString(this.getValue().architecture)) {
								result['architecture'] = this.getValue().architecture;
								form.down('#step2').applyFilter();
							}
						}
					}
				}]
			}, {
				xtype: 'fieldset',
				style: 'margin: 10px',
				title: 'Select OS',
				layout: 'column',
				items: [{
					xtype: 'panel',
					layout: 'column',
					border: false,
					bodyCls: 'scalr-ui-frame',
					itemId: 'images'
				}]
			}, {
				xtype: 'fieldset',
				style: 'margin: 10px',
				hidden:!(loadParams['beta'] == 1),
				title: 'OR Custom image',
				items: [{
					xtype: 'fieldcontainer',
					fieldLabel: 'ImageID',
					layout: 'hbox',
					items: [{
						xtype: 'textfield',
						name: 'imageId',
						value: ''
					} , {
						xtype: 'button',
						margin: {
							left:3
						},
						text: 'Set this image as prototype',
						handler: function(){
							result['imageId'] = this.prev('[name="imageId"]').getValue();
							result['isCustomImage'] = true;
							
							form.stepNext();
							form.down('#step2').setTitle('Step 2 - Choose OS [ImageID: ' + result['imageId'] + ']');
						}
					}]
				}]
			}],
			applyFilter: function () {
				var architecture = result['architecture'], location = result['location'], r = moduleParams.images[result['platform']];

				form.down('#images').items.each(function () {
					var d = true;
					for (i in r) {
						if (r[i].name == this.renderData.name && r[i].location == location && r[i].architecture == architecture) {
							d = false;
							this.imageId = i;
							break;
						}
					}

					if (this.pressed) {
						result['imageId'] = this.imageId;
						form.down('#step2').setTitle('Step 2 - Choose OS [' + this.text + ' (' + result['architecture'] + ') at ' + result['location'] + ']');
					}

					if (d) {
						if (this.pressed)
							this.toggle(false);

						this.disable(); // deactivate, if selected
					} else
						this.enable();
				});
			},
			listeners: {
				enable: function () {
					var r = moduleParams.images[result['platform']], d = [], l = [], k = [], cont = form.down('#images');

					cont.removeAll();
					form.down('#step2').setTitle('Step 2 - Choose OS');
					var added = {};
					for (i in r) {
						if (! added[r[i].name])
							cont.add({
								xtype: 'custombutton',
								width: 109,
								height: 109,
								allowDepress: false,
								toggleGroup: 'scalr-ui-roles-builder-image',

								renderTpl:
									'<div class="scalr-ui-btn-custom" id="{id}-btnEl">' +
										'<div class="{prefix}-btn-icon"><img src="{icon}"></div>' +
										'<div class="{prefix}-btn-name">{name}</div>' +
									'</div>',
								renderData: {
									prefix: 'scalr-ui-roles-builder',
									name: r[i].name,
									icon: '/ui/images/icons/os/' + r[i]['os_dist'] + '_64x64.png',
								},
								imageId: i,
								margin: 10,
								toggleHandler: function () {
									if (this.pressed) {
										result['imageId'] = this.imageId;
										form.down('[name="imageId"]').setValue('');
										form.stepNext();
										form.down('#step2').setTitle('Step 2 - Choose OS [' + this.renderData.name + ' (' + result['architecture'] + ') at ' + result['location'] + ']');
									} else {
										form.stepChanged();
										form.down('#step2').setTitle('Step 2 - Choose OS');
									}
								},
								listeners: {
									click: function () {
										if (form.layout.expandedItem == 1)
											form.stepNext();
									}
								}
							});

						added[r[i].name] = true;

						if (l.indexOf(r[i]['location']) == -1)
							l.push(r[i]['location']);
					}

					Ext.Array.each(l, function (n) {
						k.push({ name: n });
					});

					var c = form.down('#location');
					c.store.load({ data: k });
					c.setValue(result['platform'] == 'ec2' ? 'us-east-1' : l[0]);
					form.down('#architecture').setValue({ architecture: ['x86_64'] });

					result['architecture'] = 'x86_64';
					result['location'] = form.down('#location').getValue();

					form.down('#step2').applyFilter();
				}
			}
		}, {
			title: 'Step 3 - Set settings',
			itemId: 'step3',
			hideCollapseTool: true,
			disabled: true,
			autoScroll: true,
			defaults: {
				msgTarget: 'side'
			},
			items: [{
				xtype: 'fieldset',
				margin: 10,
				title: 'General',
				labelWidth: 80,
				items: [{
					xtype: 'textfield',
					fieldLabel: 'Role name',
					itemId: 'settings-rolename',
					anchor: '100%',
					validator: function (value) {
						var r = /^[A-z0-9-]+$/, r1 = /^-/, r2 = /-$/;
						if (r.test(value) && !r1.test(value) && !r2.test(value) && value.length > 2)
							return true;
						else
							return 'Illegal name';
					}
				}, {
					xtype: 'displayfield',
					fieldLabel: 'Group',
					value: 'Base images',
					itemId: 'settings-group',
					anchor: '100%'
				}]
			}, {
				xtype: 'fieldset',
				margin: 10,
				title: 'Behaviors',
				itemId: 'settings-behaviors',
				layout: {
					type: 'column'
				},
				defaults: {
					xtype: 'custombutton',
					width: 109,
					height: 109,
					enableToggle: true,

					renderTpl:
						'<div class="scalr-ui-btn-custom" style="width: 109px; height: 109px" id="{id}-btnEl">' +
							'<div class="{prefix}-btn-icon"><img src="{icon}"></div>' +
							'<div class="{prefix}-btn-name">{name}</div>' +
						'</div>',
					margin: 10
				},
				items: [{
					behavior: 'mysql',
					renderData: {
						name: 'MySQL',
						icon: '/ui/images/icons/behaviors/database_mysql.png',
						prefix: 'scalr-ui-roles-builder'
					},
					handler: checkboxBehaviorListener
				}, {
					behavior: 'postgresql',
					renderData: {
						name: 'PostgreSQL',
						icon: '/ui/images/icons/behaviors/database_postgresql.png',
						prefix: 'scalr-ui-roles-builder'
					},
					handler: checkboxBehaviorListener
				}, {
					behavior: 'app',
					renderData: {
						name: 'Apache',
						icon: '/ui/images/icons/behaviors/app_app.png',
						prefix: 'scalr-ui-roles-builder'
					},
					handler: checkboxBehaviorListener
				}, {
					behavior: 'www',
					renderData: {
						name: 'Nginx',
						icon: '/ui/images/icons/behaviors/lb_www.png',
						prefix: 'scalr-ui-roles-builder'
					},
					handler: checkboxBehaviorListener
				}, {
					behavior: 'memcached',
					renderData: {
						name: 'Memcached',
						icon: '/ui/images/icons/behaviors/cache_memcached.png',
						prefix: 'scalr-ui-roles-builder'
					},
					handler: checkboxBehaviorListener
				}, {
					behavior: 'redis',
					renderData: {
						name: 'Redis',
						icon: '/ui/images/icons/behaviors/database_redis.png',
						prefix: 'scalr-ui-roles-builder'
					},
					handler: checkboxBehaviorListener,
				}, {
					behavior: 'rabbitmq',
					renderData: {
						name: 'RabbitMQ',
						icon: '/ui/images/icons/behaviors/mq_rabbitmq.png',
						prefix: 'scalr-ui-roles-builder'
					},
					handler: checkboxBehaviorListener,
				}, {
					behavior: 'mongodb',
					renderData: {
						name: 'MongoDB',
						icon: '/ui/images/icons/behaviors/database_mongodb.png',
						prefix: 'scalr-ui-roles-builder'
					},
					handler: checkboxBehaviorListener,
				}]
			}, {
				xtype: 'fieldset',
				margin: 10,
				title:  'Addons',
				itemId: 'settings-addons',
				layout: {
					type: 'column'
				},
				defaults: {
					xtype: 'custombutton',
					width: 109,
					height: 109,
					enableToggle: true,

					renderTpl:
						'<div class="scalr-ui-btn-custom" style="width: 109px; height: 109px" id="{id}-btnEl">' +
							'<div class="{prefix}-btn-icon"><img src="{icon}"></div>' +
							'<div class="{prefix}-btn-name">{name}</div>' +
						'</div>',
					margin: 10
				},
				items: [{
					behavior: 'mysqlproxy',
					renderData: {
						name: 'MySQL Proxy',
						icon: '/ui/images/icons/behaviors/utils_mysqlproxy.png',
						prefix: 'scalr-ui-roles-builder'
					},
					handler: checkboxAddonsListener
				}, {
					behavior: 'chef',
					renderData: {
						name: 'Chef',
						icon: '/ui/images/icons/behaviors/utils_chef.png',
						prefix: 'scalr-ui-roles-builder'
					},
					pressed: true,
					toggle: Ext.emptyFn
				}]
			}, {
				xtype: 'fieldset',
				style: 'margin: 10px',
				title: 'Software',
				itemId: 'softwareSet',
				hidden: true,
				labelWidth: 80,
				items: [{
					fieldLabel: 'MySQL',
					xtype: 'combo',
					allowBlank: false,
					editable: false,
					store: [['mysql', 'MySQL 5.x'], ['percona', 'Percona Server 5.1']],
					value: 'mysql',
					name: 'mysqlServerType',
					typeAhead: false,
					queryMode: 'local',
					width: 300
				}]
			}],

			dockedItems: [{
				xtype: 'container',
				dock: 'bottom',
				cls: 'scalr-ui-docked-bottombar',
				style: 'border-width: 0px',
				layout: {
					type: 'hbox',
					pack: 'center'
				},
				items: [{
					xtype: 'button',
					width: 100,
					text: 'Create',
					handler: function () {
						if (form.down('#settings-rolename').isValid()) {
							var r = Scalr.utils.CloneObject(result);

							if (! r.behaviors.length)
								Ext.Array.include(r.behaviors, 'base');

							r['behaviors'] = Ext.encode(Ext.Array.merge(r.behaviors, r.addons));
							delete r.addons;
							r['roleName'] = form.down('#settings-rolename').getValue();
							r['mysqlServerType'] = form.down('[name="mysqlServerType"]').getValue();

							Scalr.Request({
								processBox: {
									type: 'action'
								},
								url: '/roles/xBuild',
								params: r,
								success: function (data) {
									Scalr.event.fireEvent('redirect', '#/bundletasks/' + data.bundleTaskId + '/view', true);
								}
							});
						}
					}
				}]
			}]
		}],
		listeners: {
			show: function () {
				if (platforms.length == 1) {
					form.down('#step1').child('custombutton').toggle(true);
					form.down('#step1').disable();
				}
			}
		}
	});

	return form;
});
