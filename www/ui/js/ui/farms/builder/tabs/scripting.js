Scalr.regPage('Scalr.ui.farms.builder.tabs.scripting', function () {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Scripting',

		itemId: 'scripting',
		layout: {
			type: 'hbox',
			align: 'stretch'
		},

		isEnabled: function (record) {
			return record.get('platform') != 'rds';
		},

		getDefaultValues: function (record) {
			record.set('scripting', []);
			return {};
		},

		// DEPRECATED, not used anymore method
		activateTab: function () {
			var toolbar = this.findOne('itemId', 'scripting.add').getTopToolbar();

			//console.log(this.findOne('itemId', 'scripting.edit.script').el);
			this.findOne('itemId', 'scripting.edit.script').on('expand', function () {
				var rec = this.findOne('itemId', 'scripting.edit').currentRecord;

				this.loadMask.show();
				Ext.Ajax.request({
					url: '/server/server.php',
					params: {
						_cmd: 'get_script_template_source',
						version: rec.get('version'),
						scriptid: rec.get('script_id')
					},
					success: function(response, options) {
						var content = response.responseText.replace(/&/gm, '&amp;').replace(/</gm, '&lt;').replace(/>/gm, '&gt;');
						content = '<pre style="margin: 0px"><code>' + content + '</code></pre>';
						this.findOne('itemId', 'scripting.edit.script').body.update(content);


						hljs.highlightBlock(this.findOne('itemId', 'scripting.edit.script').body.dom.firstChild.firstChild);
						this.loadMask.hide();

					},
					scope: this
				});



			}, this);

			new Ext.Resizable(this.findOne('itemId', 'scripting.edit.script').el, {
				handles: 'se',
				wrap: false,
				pinned: true,
				width: 400,
				height: 200,
				minWidth: 50,
				maxWidth: 800,
				minHeight: 50,
				maxHeight: 400
			});


		},

		beforeShowTab: function (record, handler) {
			var cloudLocation = record.get('cloud_location');

			/*if (this.cacheExist(['data', cloudLocation]))
				handler();
			else*/
				Scalr.Request({
					processBox: {
						type: 'action'
					},
					url: '/farms/builder/xGetScripts',
					params: {
						cloudLocation: cloudLocation
					},
					scope: this,
					success: function (response) {
						this.cacheSet({ scripts: response.scripts, events: response.events }, [ 'data', cloudLocation]);
						handler();
					},
					failure: function () {
						this.deactivateTab();
					}
				});
		},

		showTab: function (record) {
			this.down('#scripting_edit').hide();
			this.down('#scripting_all').store.load({ data: record.get('scripting') });

			this.down('#script').store.load({ data: this.cacheGet(['data', record.get('cloud_location')]).scripts});
			this.down('#event').store.load({ data: this.cacheGet(['data', record.get('cloud_location')]).events});

			this.down('#script').reset();
			this.down('#event').reset();
		},

		hideTab: function (record) {
			this.down('#scripting_all').getSelectionModel().deselectAll();

			var scripting = [];
			this.down('#scripting_all').store.each(function (it) {
				scripting[scripting.length] = it.data;
			});

			record.set('scripting', scripting);
		},

		items: [{
			border: true,
			width: 400,
			xtype: 'panel',
			itemId: 'scripting_add',
			layout: {
				type: 'hbox',
				align: 'stretch'
			},
			dockedItems: [{
				xtype: 'toolbar',
				dock: 'top',
				items: [{
					xtype: 'combo',
					store: {
						fields: [ 'id', 'name', 'description', 'issync', 'timeout', 'revisions' ],
						proxy: 'object'
					},
					valueField: 'id',
					displayField: 'name',
					queryMode: 'local',
					editable: false,
					itemId: 'script',
					listConfig: {
						resizable: true,
						resizeHandles: 'e',
						cls: 'x-boundlist-overflow'
					},
					flex: 1
				}, {
					xtype: 'tbtext',
					text: 'script on',
					style: 'font-size: 12px'
				}, {
					xtype: 'combo',
					store: {
						fields: [ 'id', 'name' ],
						proxy: 'object'
					},
					valueField: 'id',
					displayField: 'id',
					queryMode: 'local',
					editable: false,
					itemId: 'event',
					listConfig: {
						resizable: true,
						resizeHandles: 'e',
						cls: 'x-boundlist-overflow'
					},
					flex: 1
				}, {
					xtype: 'tbtext',
					text: 'event',
					style: 'font-size: 12px'
				}, {
					xtype: 'button',
					icon: '/ui/images/icons/add_icon_16x16.png',
					handler: function () {
						var script = this.prev('#script').getValue(), event = this.prev('#event').getValue();

						if (!script || !event) {
							Scalr.message.Error('Please select script and event');
						} else {
							var comp = this.up('#scripting').down('#scripting_all'), store = comp.store, ind = store.findBy(function (rec) {
								if (rec.get('script_id') == script && rec.get('event') == event)
									return true;
							});

							var rec = this.prev('#script').store.findRecord('id', script), version = 0, revisions = rec.get('revisions');
							for (var i = 0; i < revisions.length; i++) {
								if (parseInt(revisions[i].revision) > version)
									version = revisions[i].revision;
							}

							var newRec = {
								script: rec.get('name'),
								event: event,
								target: '',
								script_id: rec.get('id'),
								timeout: rec.get('timeout'),
								issync: rec.get('issync'),
								version: version,
								order_index: store.getCount() ? store.getCount() * 10 : 1 // TODO: replace
							};

							var r = store.add(newRec);

							this.prev('#script').reset();
							this.prev('#event').reset();
							comp.select(r);
						}
					}
				}]
			}],
			items: {
				xtype: 'dataview',
				flex: 1,
				store: {
					fields: [ 'script_id', 'script', 'event', 'target', 'issync', 'timeout', 'version', 'params', 'order_index' ],
					proxy: 'object'
				},
				border: true,
				deferEmptyText: false,
				loadMask: false,

				itemId: 'scripting_all',
				autoScroll: true,
				itemSelector: 'li',
				selectedItemCls: 'selected',
				singleSelect: true,
				emptyText: '<div class="empty">No scripts assigned to events</div>',
				cls: 'scalr-ui-farms-builder-tab-scripting',

				collectData: function(records, startIndex) {
					var groups = {}, ret = [];

					for (var i = 0, len = records.length; i < len; i++) {
						var event = records[i].get('event');
						if (! Ext.isDefined(groups[event]))
							groups[event] = { title: event, data: [] };

						groups[event].data.push({ script: records[i].data.script, order: records[i].data.order_index, disabled: records[i].get('target') == '' ,index: i + startIndex });
					}

					for (i in groups) {
						groups[i].data.sort(function (a, b) {
							return a.order - b.order;
						});

						ret.push(groups[i]);
					}

					ret.sort(function (a, b) {
						return a.title > b.title;
					})

					return ret;
				},

				tpl: new Ext.XTemplate(
					'<tpl for=".">',
						'<div class="title"><span>{title}</span></div>',
						'<ul class=""><tpl for="data">',
							'<li viewindex="{index}" class=\'<tpl if="disabled">disabled</tpl>\'>{script} ({order})</li>',
						'</tpl></ul>',
					'</tpl>'
				),

				updateIndexes: function (startIndex, endIndex) {
					var ns = this.all.elements,
						records = this.store.getRange();

					startIndex = startIndex || 0;
					endIndex = endIndex || ((endIndex === 0) ? 0 : (ns.length - 1));
					for(var i = startIndex; i <= endIndex; i++) {
						ns[i].viewIndex = ns[i].hasAttribute('viewindex') ? parseInt(ns[i].getAttribute('viewindex')) : i;
						ns[i].viewRecordId = records[ns[i].viewIndex].internalId;

						if (!ns[i].boundView) {
							ns[i].boundView = this.id;
						}
					}
				},

				onAdd: function () {
					this.refresh();
				},

				onRemove: function () {
					this.refresh();
				},

				onUpdate: function () {
					this.refresh()
				},

				listeners: {
					selectionchange: function (dataview, selections) {
						// save previous record
						var scr = this.up('#scripting'), rec = scr.down('#scripting_edit').currentRecord, fieldset = scr.down('#scripting_edit_parameters'), params = {};
						if (rec) {
							rec.set('target', scr.down('[name="scripting.edit.target"]').getValue());
							rec.set('issync', scr.down('[name="scripting.edit.issync"]').getValue());
							rec.set('timeout', scr.down('[name="scripting.edit.timeout"]').getValue());
							rec.set('version', scr.down('[name="scripting.edit.version"]').getValue());
							rec.set('order_index', scr.down('[name="scripting.edit.order_index"]').getValue());

							fieldset.items.each(function (item) {
								params[item.paramName] = item.getValue();
							});
							fieldset.removeAll();

							rec.set('params', params);
							scr.down('#scripting_edit').currentRecord = null;
						}

						this.refresh();

						if (selections.length) {
							scr.down('#scripting_edit').show();
							var rec = selections[0], script = scr.down('#script').store.findRecord('id', rec.get('script_id'));

							scr.down('#scripting_edit').currentRecord = rec;
							scr.down('[name="scripting.edit.when"]').setValue(
								scr.down('#event').store.findRecord('id', rec.get('event')).get('name')
							);
							scr.down('[name="scripting.edit.do"]').setValue(
								script.get('description')
							);

							var data = [ [ '', '-- DO NOT EXECUTE SCRIPT --' ], [ 'farm', 'All instances in the farm' ] ];
							if (rec.get('event') != 'DNSZoneUpdated')
								data.push(['role', 'All instances of this role']);

							if (rec.get('event') != 'HostDown' && rec.get('event') != 'DNSZoneUpdated')
								data.push(['instance', 'That instance only']);

							scr.down('[name="scripting.edit.target"]').store.loadData(data);

							scr.down('[name="scripting.edit.target"]').setValue(rec.get('target'));
							scr.down('[name="scripting.edit.issync"]').setValue(rec.get('issync'));
							scr.down('[name="scripting.edit.timeout"]').setValue(rec.get('timeout'));
							scr.down('[name="scripting.edit.order_index"]').setValue(rec.get('order_index'));

							scr.down('[name="scripting.edit.version"]').store.load({ data: script.get('revisions') });
							scr.down('[name="scripting.edit.version"]').store.sort('revision', 'DESC');
							scr.down('[name="scripting.edit.version"]').reset();

							if (rec.get('version') == 'latest') {
								var latest = 1;
								var scripts = script.get('revisions');
								for (i in scripts) {
									if (scripts[i].revision > latest)
										latest = scripts[i].revision;
								}

								scr.down('[name="scripting.edit.version"]').setValue(parseInt(latest));
							} else {
								scr.down('[name="scripting.edit.version"]').setValue(parseInt(rec.get('version')));
							}

							//scr.down('[name="scripting.edit.version"]').fireEvent('select', this.findOne('name', 'scripting.edit.version'),
								//this.findOne('name', 'scripting.edit.version').store.getById(this.findOne('name', 'scripting.edit.version').getValue())
							//);

							var fieldset = scr.down('#scripting_edit_parameters'), params = rec.get('params');
							for (var i in params) {
								var f = fieldset.child('[paramName="' + i + '"]');
								if (f)
									f.setValue(params[i]);
							}

							scr.down('#scripting_edit_script').collapse();

						} else {
							scr.down('#scripting_edit').currentRecord = null;
							scr.down('#scripting_edit').hide();
						}
					}
				}
			}
		}, {
			flex: 1,
			border: false,
			autoScroll: true,
			itemId: 'scripting_edit',
			margin: {
				left: 10
			},
			bodyCls: 'scalr-ui-frame',
			items: [{
				xtype: 'fieldset',
				title: 'General',
				defaults: {
					labelWidth: 150,
					anchor: '100%'
				},
				items: [{
					xtype: 'displayfield',
					fieldLabel: 'When',
					name: 'scripting.edit.when'
				}, {
					xtype: 'displayfield',
					fieldLabel: 'Do',
					name: 'scripting.edit.do'
				}, {
					xtype: 'combo',
					store: [ [ '', '-- DO NOT EXECUTE SCRIPT --' ], ['instance', 'That instance only'], ['role', 'All instances of this role'], [ 'farm', 'All instances in the farm' ]],
					queryMode: 'local',
					editable: false,
					name: 'scripting.edit.target',
					fieldLabel: 'Where',
					listeners: {
						change: function () {
							this.up('#scripting_edit').currentRecord.set('target', this.getValue());
						}
					}
				}, {
					xtype: 'combo',
					store: [ ['1', 'Synchronous'], ['0', 'Asynchronous']],
					queryMode: 'local',
					editable: false,
					name: 'scripting.edit.issync',
					fieldLabel: 'Execution mode'
				}, {
					xtype: 'textfield',
					fieldLabel: 'Timeout',
					name: 'scripting.edit.timeout',
					allowBlank: false,
					regex: /^[0-9]+$/
				},{
					xtype: 'textfield',
					fieldLabel: 'Execution order',
					name: 'scripting.edit.order_index',
					allowBlank: false,
					regex: /^[0-9]+$/,
					listeners: {
						change: function () {
							this.up('#scripting_edit').currentRecord.set('order_index', this.getValue());
						}
					}
				}, {
					xtype: 'combo',
					store: {
						fields: [{ name: 'revision', type: 'int' }, 'fields' ],
						proxy: 'object'
					},
					valueField: 'revision',
					displayField: 'revision',
					queryMode: 'local',
					editable: false,
					name: 'scripting.edit.version',
					fieldLabel: 'Version',
					listeners: {
						change: function () {
							if (this.getValue()) {
								var fields = this.store.findRecord('revision', this.getValue()).get('fields') || '',
									fieldset = this.up('#scripting_edit').down('#scripting_edit_parameters');

								if (Ext.isObject(fields)) {
									var values = {};
									fieldset.items.each(function (item) {
										values[item.paramName] = item.getValue();
									});
									fieldset.show();
									fieldset.removeAll();

									for (var i in fields) {
										fieldset.add({
											xtype: 'textfield',
											fieldLabel: fields[i],
											paramName: i,
											value: values[i] || '',

											//grow: true,
											//growMin: 10,
											//growMax: 100
										});
									}
								} else {
									fieldset.removeAll();
									fieldset.hide();
								}
							}
						}
					}
				}]
			}, {
				xtype: 'fieldset',
				itemId: 'scripting_edit_parameters',
				title: 'Parameters',
				defaults: {
					anchor: '100%',
					labelWidth: 150
				},
				hidden: true
			}, {
				xtype: 'fieldset',
				hidden: true,
				itemId: 'scripting_edit_script',
				checkboxToggle: true,
				title: 'Script source',
				autoScroll: true
			}, {
				xtype: 'button',
				itemId: 'scripting_delete_script',
				text: 'Delete',
				handler: function () {
					var rec = this.up('#scripting_edit').currentRecord;
					this.up('#scripting').down('#scripting_all').getSelectionModel().deselectAll();
					this.up('#scripting').down('#scripting_all').store.remove(rec);
				}
			}]
		}]
	});
});
