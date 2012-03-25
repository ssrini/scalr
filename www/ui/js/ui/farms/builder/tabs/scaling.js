Scalr.regPage('Scalr.ui.farms.builder.tabs.scaling', function (moduleTabParams) {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Scaling options',
		itemId: 'scaling',

		addAlgoTab: function (metric, values, activate) {
			var tabpanel = this.down('#algos'), p = null, alias = metric.get('alias'), field = 'scaling.' + metric.get('id') + '.';
			if (alias == 'bw') {
				p = tabpanel.add({
					title: metric.get('name'),
					alias: metric.get('alias'),
					metricId: metric.get('id'),
					field: field,
					getValues: function (comp) {
						return {
							type: this.down('[name="'+ comp.field + 'type"]').getValue(),
							min: this.down('[name="' + comp.field + 'min"]').getValue(),
							max: this.down('[name="' + comp.field + 'max"]').getValue()
						};
					},
					items: [{
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Use'
						}, {
							xtype: 'combo',
							hideLabel: true,
							store: [ 'inbound', 'outbound' ],
							allowBlank: false,
							editable: false,
							name: field + 'type',
							queryMode: 'local',
							margin: {
								left: 3
							},
							width: 100
						}, {
							xtype: 'displayfield',
							value: ' bandwidth usage value for scaling',
							margin: {
								left: 3
							}
						}]
					}, {
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Scale in (release instances) when average bandwidth usage on role is less than'
						}, {
							xtype: 'textfield',
							name: field + 'min',
							margin: {
								left: 3
							},
							width: 40
						}, {
							xtype: 'displayfield',
							value: 'Mbit/s',
							margin: {
								left: 3
							}
						}]
					}, {
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Scale out (add more instances) when average bandwidth usage on role is more than'
						}, {
							xtype: 'textfield',
							name: field + 'max',
							margin: {
								left: 3
							},
							width: 40
						}, {
							xtype: 'displayfield',
							value: 'Mbit/s',
							margin: {
								left: 3
							}
						}]
					}]
				});

				this.down('[name="' + field + 'min"]').setValue(values['min'] || '10');
				this.down('[name="' + field + 'max"]').setValue(values['max'] || '40');
				this.down('[name="' + field + 'type"]').setValue(values['type'] || 'outbound');

			} else if (alias == 'la') {
				p = tabpanel.add({
					title: metric.get('name'),
					alias: metric.get('alias'),
					metricId: metric.get('id'),
					field: field,
					getValues: function (comp) {
						return {
							period: this.down('[name="' + comp.field + 'period"]').getValue(),
							min: this.down('[name="' + comp.field + 'min"]').getValue(),
							max: this.down('[name="' + comp.field + 'max"]').getValue()
						};
					},
					items: [{
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Use'
						}, {
							xtype: 'combo',
							hideLabel: true,
							store: ['1','5','15'],
							allowBlank: false,
							editable: false,
							name: field + 'period',
							queryMode: 'local',
							margin: {
								left: 3
							},
							width: 80
						}, {
							xtype: 'displayfield',
							value: 'minute(s) load averages for scaling',
							margin: {
								left: 3
							}
						}]
					}, {
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Scale in (release instances) when LA goes under'
						}, {
							xtype: 'textfield',
							name: field + 'min',
							margin: {
								left: 3
							},
							width: 40
						}]
					}, {
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Scale out (add more instances) when LA goes over'
						}, {
							xtype: 'textfield',
							name: field + 'max',
							margin: {
								left: 3
							},
							width: 40
						}]
					}]
				});

				this.down('[name="' + field + 'min"]').setValue(values['min'] || '2');
				this.down('[name="' + field + 'max"]').setValue(values['max'] || '5');
				this.down('[name="' + field + 'period"]').setValue(values['period'] || '15');

			} else if (alias == 'sqs') {
				p = tabpanel.add({
					title: metric.get('name'),
					alias: metric.get('alias'),
					metricId: metric.get('id'),
					field: field,
					getValues: function (comp) {
						return {
							queue_name: this.down('[name="' + comp.field + 'queue_name"]').getValue(),
							min: this.down('[name="' + comp.field + 'min"]').getValue(),
							max: this.down('[name="' + comp.field + 'max"]').getValue()
						};
					},
					items: [{
						fieldLabel: 'Queue name',
						xtype: 'textfield',
						name: field + 'queue_name',
						labelWidth: 80,
						width: 300
					}, {
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Scale out (add more instances) when queue size goes over'
						}, {
							xtype: 'textfield',
							name: field + 'max',
							margin: {
								left: 3
							},
							width: 40
						}, {
							xtype: 'displayfield',
							value: 'items',
							margin: {
								left: 3
							}
						}]
					}, {
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Scale in (release instances) when queue size goes under'
						}, {
							xtype: 'textfield',
							name: field + 'min',
							margin: {
								left: 3
							},
							width: 40
						}, {
							xtype: 'displayfield',
							value: 'items',
							margin: {
								left: 3
							}
						}]
					}]
				});

				this.down('[name="' + field + 'min"]').setValue(values['min'] || '');
				this.down('[name="' + field + 'max"]').setValue(values['max'] || '');
				this.down('[name="' + field + 'queue_name"]').setValue(values['queue_name'] || '');

			} else if (alias == 'custom') {
				p = tabpanel.add({
					title: metric.get('name'),
					field: field,
					alias: metric.get('alias'),
					metricId: metric.get('id'),
					getValues: function (comp) {
						return {
							min: this.down('[name="' + comp.field + 'min"]').getValue(),
							max: this.down('[name="' + comp.field + 'max"]').getValue()
						};
					},
					items: [{
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Scale out (add more instances) when metric value goes over'
						}, {
							xtype: 'textfield',
							name: field + 'max',
							margin: {
								left: 3
							},
							width: 40
						}]
					}, {
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Scale in (release instances) when metric value goes under'
						}, {
							xtype: 'textfield',
							name: field + 'min',
							margin: {
								left: 3
							},
							width: 40
						}]
					}]
				});

				this.down('[name="' + field + 'min"]').setValue(values['min'] || '');
				this.down('[name="' + field + 'max"]').setValue(values['max'] || '');

			} else if (alias == 'ram') {
				p = tabpanel.add({
					title: metric.get('name'),
					alias: metric.get('alias'),
					metricId: metric.get('id'),
					field: field,
					getValues: function (comp) {
						return {
							use_cached: this.down('[name="' + comp.field + 'use_cached"]').getValue(),
							min: this.down('[name="' + comp.field + 'min"]').getValue(),
							max: this.down('[name="' + comp.field + 'max"]').getValue()
						};
					},
					items: [{
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Scale out (add more instances) when free RAM goes under'
						}, {
							xtype: 'textfield',
							name: field + 'min',
							margin: {
								left: 3
							},
							width: 40
						}, {
							xtype: 'displayfield',
							value: 'MB',
							margin: {
								left: 3
							}
						}]
					}, {
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Scale in (release instances) when free RAM goes over'
						}, {
							xtype: 'textfield',
							name: field + 'max',
							margin: {
								left: 3
							},
							width: 40
						}, {
							xtype: 'displayfield',
							value: 'MB',
							margin: {
								left: 3
							}
						}]
					}, {
						xtype: 'checkbox',
						boxLabel: 'Use free+cached ram as scaling metric',
						name: field + 'use_cached',
						inputValue: '1'
					}]
				});

				this.down('[name="' + field + 'use_cached"]').setValue(values['use_cached'] || false);
				this.down('[name="' + field + 'min"]').setValue(values['min'] || '');
				this.down('[name="' + field + 'max"]').setValue(values['max'] || '');

			} else if (alias == 'http') {
				p = tabpanel.add({
					title: metric.get('name'),
					alias: metric.get('alias'),
					metricId: metric.get('id'),
					field: field,
					getValues: function (comp) {
						return {
							url: this.down('[name="' + comp.field + 'url"]').getValue(),
							min: this.down('[name="' + comp.field + 'min"]').getValue(),
							max: this.down('[name="' + comp.field + 'max"]').getValue()
						};
					},
					items: [{
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Scale out (add more instances) when URL response time more than'
						}, {
							xtype: 'textfield',
							name: field + 'max',
							margin: {
								left: 3
							},
							width: 40
						}, {
							xtype: 'displayfield',
							value: 'seconds',
							margin: {
								left: 3
							}
						}]
					}, {
						xtype: 'fieldcontainer',
						layout: 'hbox',
						hideLabel: true,
						items: [{
							xtype: 'displayfield',
							value: 'Scale in (release instances) when URL response time less than'
						}, {
							xtype: 'textfield',
							name: field + 'min',
							margin: {
								left: 3
							},
							width: 40
						}, {
							xtype: 'displayfield',
							value: 'seconds',
							margin: {
								left: 3
							}
						}]
					}, {
						xtype: 'textfield',
						fieldLabel: 'URL (with http(s)://)',
						name: field + 'url',
						labelWidth: 120,
						anchor: '100%'
					}]
				});

				this.down('[name="' + field + 'min"]').setValue(values['min'] || '1');
				this.down('[name="' + field + 'max"]').setValue(values['max'] || '5');
				this.down('[name="' + field + 'url"]').setValue(values['url'] || '');

			} else if (alias == 'time') {
				var store = Ext.create('Ext.data.Store', {
					fields: [ 'start_time', 'end_time', 'week_days', 'instances_count', 'id' ],
					proxy: 'object'
				});

				var currentTimeZone = 'Current time zone is: <span style="font-weight: bold;">' + moduleTabParams['currentTimeZone'] +
					'</span> (' + moduleTabParams['currentTime'] + '). <a target="_blank" href="#/environments/' + moduleTabParams['currentEnvId'] +
					'/edit">Click here</a> if you want to change it.</div>';

				p = tabpanel.add({
					title: metric.get('name'),
					alias: metric.get('alias'),
					metricId: metric.get('id'),
					field: field,
					store: store,
					bodyPadding: 0,
					getValues: function (comp) {
						var data = [], records = comp.store.getRange();
						for (var i = 0; i < records.length; i++)
							data[data.length] = records[i].data;

						return data;
					},
					dockedItems: [{
						dock: 'top',
						xtype: 'toolbar',
						items: [{
							xtype: 'tbtext',
							text: currentTimeZone,
							style: 'font-size: 12px'
						}, '-', {
							xtype: 'button',
							icon: '/ui/images/icons/add_icon_16x16.png',
							itemId: 'add',
							handler: function () {
								Scalr.Confirm({
									form: [{
										xtype: 'timefield',
										fieldLabel: 'Start time',
										name: 'ts_s_time',
										anchor: '100%',
										minValue: '0:15am',
										maxValue: '23:45pm',
										allowBlank: false
									}, {
										xtype: 'timefield',
										fieldLabel: 'End time',
										name: 'ts_e_time',
										anchor: '100%',
										minValue: '0:15am',
										maxValue: '23:45pm',
										allowBlank: false
									}, {
										xtype: 'checkboxgroup',
										fieldLabel: 'Days of week',
										columns: 3,
										items: [
											{ boxLabel: 'Sun', name: 'ts_dw_Sun', width: 50 },
											{ boxLabel: 'Mon', name: 'ts_dw_Mon' },
											{ boxLabel: 'Tue', name: 'ts_dw_Tue' },
											{ boxLabel: 'Wed', name: 'ts_dw_Wed' },
											{ boxLabel: 'Thu', name: 'ts_dw_Thu' },
											{ boxLabel: 'Fri', name: 'ts_dw_Fri' },
											{ boxLabel: 'Sat', name: 'ts_dw_Sat' }
										]
									}, {
										xtype: 'numberfield',
										fieldLabel: 'Instances count',
										name: 'ts_instances_count',
										anchor: '100%',
										allowDecimals: false,
										minValue: 0,
										allowBlank: false
									}],
									ok: 'Add',
									title: 'Add new time scaling period',
									formValidate: true,
									closeOnSuccess: true,
									scope: this,
									success: function (formValues) {
										var week_days_list = '';
										var i = 0;

										for (k in formValues) {
											if (k.indexOf('ts_dw_') != -1 && formValues[k] == 'on') {
												week_days_list += k.replace('ts_dw_','')+', ';
												i++;
											}
										}

										if (i == 0) {
											Scalr.message.Error('You should select at least one week day');
											return false;
										}
										else
											week_days_list = week_days_list.substr(0, week_days_list.length-2);

										var int_s_time = parseInt(formValues.ts_s_time.replace(/\D/g,''));
										var int_e_time = parseInt(formValues.ts_e_time.replace(/\D/g,''));

										if (formValues.ts_s_time.indexOf('AM') && int_s_time >= 1200)
											int_s_time = int_s_time-1200;

										if (formValues.ts_e_time.indexOf('AM') && int_e_time >= 1200)
											int_e_time = int_e_time-1200;

										if (formValues.ts_s_time.indexOf('PM') != -1)
											int_s_time = int_s_time+1200;

										if (formValues.ts_e_time.indexOf('PM') != -1)
											int_e_time = int_e_time+1200;

										if (int_e_time <= int_s_time) {
											Scalr.message.Error('End time value must be greater than Start time value');
											return false;
										}

										var record_id = int_s_time+':'+int_e_time+':'+week_days_list+':'+formValues.ts_instances_count;

										var recordData = {
											start_time: formValues.ts_s_time,
											end_time: formValues.ts_e_time,
											instances_count: formValues.ts_instances_count,
											week_days: week_days_list,
											id: record_id
										};

										var list_exists = false;
										var list_exists_overlap = false;
										var week_days_list_array = week_days_list.split(", ");

										store.each(function (item, index, length) {
											if (item.data.id == recordData.id) {
												Scalr.message.Error('Such record already exists');
												list_exists = true;
												return false;
											}

											var chunks = item.data.id.split(':');
											var s_time = chunks[0];
											var e_time = chunks[1];
											if (
												(int_s_time >= s_time && int_s_time <= e_time) ||
												(int_e_time >= s_time && int_e_time <= e_time)
											)
											{
												var week_days_list_array_item = (chunks[2]).split(", ");
												for (var ii = 0; ii < week_days_list_array_item.length; ii++)
												{
													for (var kk = 0; kk < week_days_list_array.length; kk++)
													{
														if (week_days_list_array[kk] == week_days_list_array_item[ii] && week_days_list_array[kk] != '')
														{
															list_exists_overlap = "Period "+week_days_list+" "+formValues.ts_s_time+" - "+formValues.ts_e_time+" overlaps with period "+chunks[2]+" "+item.data.start_time+" - "+item.data.end_time;
															return true;
														}
													}
												}
											}
										}, this);

										if (!list_exists && !list_exists_overlap) {
											store.add(recordData);
											return true;
										} else {
											Scalr.message.Error((!list_exists_overlap) ? 'Such record already exists' : list_exists_overlap);
											return false;
										}
									}
								});
							}
						}]
					}],
					items: {
						xtype: 'grid',
						border: false,
						store: store,
						forceFit: true,
						plugins: {
							ptype: 'gridstore'
						},
						viewConfig: {
							emptyText: "No periods defined"
						},
						columns: [
							{ header: "Start time", width: 100, sortable: true, dataIndex: 'start_time' },
							{ header: "End time", width: 100, sortable: true, dataIndex: 'end_time' },
							{ header: "Week days", width: 150, sortable: true, dataIndex: 'week_days' },
							{ header: "Instances count", width: 180, sortable: true, dataIndex: 'instances_count', align: 'center' },
							{ header: "&nbsp;", width: 20, sortable: false, dataIndex: 'id', align:'center', xtype: 'templatecolumn',
								tpl: '<img class="delete" src="/ui/images/icons/delete_icon_16x16.png">'
							}
						],
						listeners: {
							itemclick: function (view, record, item, index, e) {
								if (e.getTarget('img.delete'))
									view.store.remove(record);
							}
						}
					}
				});

				p.on('removed', function () {
					var el = this.down('[name="scaling.max_instances"]');
					if (el)
						el.enable();
				}, this);

				this.down('[name="scaling.max_instances"]').disable();

				store.loadData(values);
			}

			if (p) {
				p.on('removed', function () {
					var el = this.down('#algos');
					if (el) {
						if (this.down('#algos').items.length == 0) {
							this.down('#algos').hide();
							this.down('#algos_disabled').show();
						}
					}
				}, this);

				this.down('#algos_disabled').hide();
				this.down('#algos').show();

				if (activate)
					tabpanel.setActiveTab(p);
			}
		},

		isEnabled: function (record) {
			var retval = record.get('platform') != 'rds' && !record.get('behaviors').match('mongodb');
			return retval;
		},

		getDefaultValues: function (record) {
			return {
				'scaling.min_instances': 1,
				'scaling.max_instances': 2,
				'scaling.polling_interval': 1,
				'scaling.keep_oldest': 0,
				'scaling.safe_shutdown': 0,
				'scaling.exclude_dbmsr_master' : 0,
				'scaling.one_by_one' : 0,
				'scaling.enabled' : 1
			};
		},

		beforeShowTab: function (record, handler) {
			if (this.cacheExist('metrics'))
				handler();
			else
				Scalr.Request({
					processBox: {
						type: 'action'
					},
					url: '/scaling/metrics/xGetList',
					scope: this,
					success: function (data) {
						this.cacheSet(data.metrics, 'metrics');
						handler();
					}
				});
		},

		showTab: function (record) {
			var settings = record.get('settings'), scaling = record.get('scaling');

			var isCfRole = (record.get('behaviors').match("cf_cloud_controller") || record.get('behaviors').match("cf_health_manager"));

			if (record.get('behaviors').match('rabbitmq') || isCfRole)
				Ext.each(this.query('field'), function(item){
					if (item.name != 'scaling.min_instances' || isCfRole || !record.get('new'))
						item.disable();
				});
			else
				Ext.each(this.query('field'), function(item){
					item.enable();
				});

			this.down('[name="enable_scaling"]').store.load({ data: this.cacheGet('metrics') });

			if (record.get('generation') == 2)
				this.down('#scaling_safe_shutdown_compositefield').show();
			else
				this.down('#scaling_safe_shutdown_compositefield').hide();

			if (settings['scaling.enabled'] == 1)
				this.down('[name="scaling.enabled"]').expand();
			else
				this.down('[name="scaling.enabled"]').collapse();

			this.down('[name="scaling.min_instances"]').setValue(settings['scaling.min_instances'] || 1);
			this.down('[name="scaling.max_instances"]').setValue(settings['scaling.max_instances'] || 2);
			this.down('[name="scaling.polling_interval"]').setValue(settings['scaling.polling_interval'] || 1);
			this.down('[name="scaling.keep_oldest"]').setValue(settings['scaling.keep_oldest'] == 1 ? true : false);
			this.down('[name="scaling.safe_shutdown"]').setValue(settings['scaling.safe_shutdown'] == 1 ? true : false);
			this.down('[name="scaling.exclude_dbmsr_master"]').setValue(settings['scaling.exclude_dbmsr_master'] == 1 ? true : false);
			this.down('[name="scaling.one_by_one"]').setValue(settings['scaling.one_by_one'] == 1 ? true : false);

			if (settings['scaling.upscale.timeout_enabled'] == 1) {
				this.down('[name="scaling.upscale.timeout_enabled"]').setValue(true);
				this.down('[name="scaling.upscale.timeout"]').enable();
			} else {
				this.down('[name="scaling.upscale.timeout_enabled"]').setValue(false);
				this.down('[name="scaling.upscale.timeout"]').disable();
			}
			this.down('[name="scaling.upscale.timeout"]').setValue(settings['scaling.upscale.timeout'] || 10);

			if (settings['scaling.downscale.timeout_enabled'] == 1) {
				this.down('[name="scaling.downscale.timeout_enabled"]').setValue(true);
				this.down('[name="scaling.downscale.timeout"]').enable();
			} else {
				this.down('[name="scaling.downscale.timeout_enabled"]').setValue(false);
				this.down('[name="scaling.downscale.timeout"]').disable();
			}
			this.down('[name="scaling.downscale.timeout"]').setValue(settings['scaling.downscale.timeout'] || 10);
			this.down('[name="enable_scaling"]').reset();

			// algos
			this.down('#algos').removeAll();
			var store = this.down('[name="enable_scaling"]').store;

			if (Ext.isObject(scaling)) {
				for (var i in scaling) {
					this.addAlgoTab(store.getById(i), scaling[i], false);
				}
			}

			if (this.down('#algos').items.length) {
				this.down('#algos').show();
				this.down('#algos_disabled').hide();
				this.down('#algos').setActiveTab(this.down('#algos').items.get(0));
			} else {
				this.down('#algos').hide();
				this.down('#algos_disabled').show();
			}
		},

		hideTab: function (record) {
			var settings = record.get('settings');
			var scaling = {};

			if (! this.down('[name="scaling.enabled"]').collapsed) {
				settings['scaling.enabled'] = '1';
			} else {
				settings['scaling.enabled'] = '0';
			}

			settings['scaling.min_instances'] = this.down('[name="scaling.min_instances"]').getValue();
			settings['scaling.max_instances'] = this.down('[name="scaling.max_instances"]').getValue();
			settings['scaling.polling_interval'] = this.down('[name="scaling.polling_interval"]').getValue();
			settings['scaling.keep_oldest'] = this.down('[name="scaling.keep_oldest"]').getValue() == true ? 1 : 0;
			settings['scaling.safe_shutdown'] = this.down('[name="scaling.safe_shutdown"]').getValue() == true ? 1 : 0;
			settings['scaling.exclude_dbmsr_master'] = this.down('[name="scaling.exclude_dbmsr_master"]').getValue() == true ? 1 : 0;
			settings['scaling.one_by_one'] = this.down('[name="scaling.one_by_one"]').getValue() == true ? 1 : 0;

			if (this.down('[name="scaling.upscale.timeout_enabled"]').getValue()) {
				settings['scaling.upscale.timeout_enabled'] = 1;
				settings['scaling.upscale.timeout'] = this.down('[name="scaling.upscale.timeout"]').getValue();
			} else {
				settings['scaling.upscale.timeout_enabled'] = 0;
				delete settings['scaling.upscale.timeout'];
			}

			if (this.down('[name="scaling.downscale.timeout_enabled"]').getValue()) {
				settings['scaling.downscale.timeout_enabled'] = 1;
				settings['scaling.downscale.timeout'] = this.down('[name="scaling.downscale.timeout"]').getValue();
			} else {
				settings['scaling.downscale.timeout_enabled'] = 0;
				delete settings['scaling.downscale.timeout'];
			}

			// algos
			this.down('#algos').items.each(function (it) {
				scaling[it.metricId.toString()] = it.getValues.call(this, it);
			}, this);

			record.set('settings', settings);
			record.set('scaling', scaling);
		},

		items: [{
			xtype: 'fieldset',
			title: 'Enable scaling',
			name: 'scaling.enabled',
			checkboxToggle: true,
			items: [{
				xtype: 'fieldset',
				title: 'General',
				defaults: {
					labelWidth: 120
				},
				items: [{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					fieldLabel: 'Minimum instances',
					items: [{
						xtype: 'textfield',
						name: 'scaling.min_instances',
						width: 40
					}, {
						xtype: 'displayfield',
						margin: {
							left: 3
						},
						value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
						listeners: {
							afterrender: function () {
								Ext.create('Ext.tip.ToolTip', {
									target: this.el.down('img.tipHelp'),
									dismissDelay: 0,
									html: 'Always keep at least this many running instances.'
								});
							}
						}
					}]
				}, {
					xtype: 'fieldcontainer',
					layout: 'hbox',
					fieldLabel: 'Maximum instances',
					items: [{
						xtype: 'textfield',
						name: 'scaling.max_instances',
						width: 40
					}, {
						xtype: 'displayfield',
						margin: {
							left: 3
						},
						value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
						listeners: {
							afterrender: function () {
								Ext.create('Ext.tip.ToolTip', {
									target: this.el.down('img.tipHelp'),
									dismissDelay: 0,
									html: 'Always keep at least this many running instances.'
								});
							}
						}
					}]
				}, {
					xtype: 'fieldcontainer',
					layout: 'hbox',
					hideLabel: true,
					items: [{
						xtype: 'displayfield',
						value: 'Polling interval (every)'
					}, {
						xtype: 'textfield',
						name: 'scaling.polling_interval',
						margin: {
							left: 3
						},
						width: 40
					}, {
						xtype: 'displayfield',
						margin: {
							left: 3
						},
						value: 'minute(s)'
					}]
				}, {
					xtype: 'checkbox',
					name: 'scaling.one_by_one',
					boxLabel: 'Do not up-scale role if there is at least one pending instance'
				}, {
					xtype: 'checkbox',
					name: 'scaling.exclude_dbmsr_master',
					boxLabel: 'Exclude database master from scaling metrics calculations'
				}, {
					xtype: 'checkbox',
					name: 'scaling.keep_oldest',
					boxLabel: 'Keep oldest instance running after scale down'
				}, {
					xtype: 'fieldcontainer',
					layout: 'hbox',
					itemId: 'scaling_safe_shutdown_compositefield',
					hideLabel: true,
					items: [{
						xtype: 'checkbox',
						name: 'scaling.safe_shutdown',
						width: 250,
						boxLabel: 'Enable safe shutdown during downscaling'
					}, {
						xtype: 'displayfield',
						margin: {
							left: 3
						},
						value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
						listeners: {
							afterrender: function () {
								Ext.create('Ext.tip.ToolTip', {
									target: this.el.down('img.tipHelp'),
									dismissDelay: 0,
									html: 'Scalr will terminate instance ONLY if script \'/usr/local/scalarizr/hooks/auth-shutdown\' return 1. ' +
										'If script not found or return any other value Scalr WON\'T terminate this server.'
								});
							}
						}
					}]
				}]
			}, {
				xtype: 'fieldset',
				title: 'Delays',
				labelWidth: 120,
				items: [{
					xtype: 'fieldcontainer',
					layout: 'hbox',
					hideLabel: true,
					items: [{
						xtype: 'checkbox',
						boxLabel: 'Wait',
						name: 'scaling.upscale.timeout_enabled',
						handler: function (checkbox, checked) {
							if (checked)
								this.next('[name="scaling.upscale.timeout"]').enable();
							else
								this.next('[name="scaling.upscale.timeout"]').disable();
						}
					}, {
						xtype: 'textfield',
						name: 'scaling.upscale.timeout',
						margin: {
							left: 3
						},
						width: 40
					}, {
						xtype: 'displayfield',
						margin: {
							left: 3
						},
						value: 'minute(s) after a new instance have been started before the next up-scale'
					}]
				}, {
					xtype: 'fieldcontainer',
					layout: 'hbox',
					hideLabel: true,
					items: [{
						xtype: 'checkbox',
						hideLabel: true,
						boxLabel: 'Wait',
						name: 'scaling.downscale.timeout_enabled',
						handler: function (checkbox, checked) {
							if (checked)
								this.next('[name="scaling.downscale.timeout"]').enable();
							else
								this.next('[name="scaling.downscale.timeout"]').disable();
						}
					}, {
						xtype: 'textfield',
						name: 'scaling.downscale.timeout',
						margin: {
							left: 3
						},
						width: 40
					}, {
						xtype: 'displayfield',
						margin: {
							left: 3
						},
						value: 'minute(s) after a shutdown before shutting down another instance'
					}]
				}]
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				fieldLabel: 'Enable scaling based on',
				labelWidth: 150,
				items: [{
					xtype: 'combo',
					store: {
						fields: [ 'id', 'name', 'alias' ],
						proxy: 'object'
					},
					valueField: 'id',
					displayField: 'name',
					editable: false,
					queryMode: 'local',
					name: 'enable_scaling',
					width: 200
				}, {
					xtype: 'displayfield',
					value: '<img src="/ui/images/icons/add_icon_16x16.png">',
					margin: {
						left: 3
					},
					name: 'enable_scaling_add',
					listeners: {
						afterrender: function () {
							this.el.down('img').on('click', function () {
								var combo = this.prev('[name="enable_scaling"]'), value = combo.store.findRecord('id', combo.getValue()), tab = this.up('#scaling');
								if (value) {
									var items = tab.down('#algos').items.items;

									for (var i = 0; i < items.length; i++) {
										if (items[i].alias == value.get('alias')) {
											Scalr.message.Error('This algoritm already added');
											return;
										}
									}

									if (value.get('alias') == 'time' && items.length) {
										Scalr.message.Error('This algoritm cannot be used with others');
										return;
									} else if (value.get('alias') != 'time' && items.length) {
										for (var i = 0; i < items.length; i++) {
											if (items[i].alias == 'time') {
												Scalr.message.Error("This algoritm cannot be used with 'Time and Day of week'");
												return;
											}
										}
									}

									tab.addAlgoTab(value, {}, true);
									combo.reset();
									Scalr.message.Flush();
								} else {
									Scalr.message.Error('Please select scaling algoritm');
								}
							}, this);
						}
					}
				}]
			}, {
				itemId: 'algos_disabled',
				bodyPadding: 10,
				style: 'font-size:12px;',
				html: 'Scaling disabled for this role',
				margin: {
					bottom: 10
				}
			}, {
				xtype: 'tabpanel',
				itemId: 'algos',
				enableTabScroll: true,
				deferredRender: false, // TODO: check in 4.1
				defaults: {
					layout: 'anchor',
					closable: true,
					border: false,
					bodyPadding: 10,
					bodyCls: 'scalr-ui-frame'
				},
				margin: {
					bottom: 10
				}
			}]
		}]
	});
});
