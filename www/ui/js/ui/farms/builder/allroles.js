Ext.define('Scalr.ui.FarmBuilderRoleAll', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.farmroleall',

	platformFilter: {},
	layout: 'fit',
	view: {
		store: {
			fields: [
				{ name: 'role_id', type: 'int' },
				'arch',
				'group',
				'name',
				'generation',
				'behaviors',
				'origin',
				{ name: 'isstable', type: 'boolean' },
				'platforms',
				'locations',
				'os',
				'tags'
			],
			proxy: 'object'
		},

		tpl: [
			'<tpl for=".">',
				'<div class="block">',
				'<div groupid="{groupid}" class="title',
				'<tpl if="records.length &gt; 0 && status == \'contract\'"> title-contract</tpl>',
				'<tpl if="records.length == 0"> title-disabled</tpl>',
				'"><div><span>{title}</span></div></div>',

				'<tpl if="records.length">',
				'<ul',
				'<tpl if="status == &quot;contract&quot;"> class="hidden"</tpl>',
				'>',
					'<tpl for="records">',
						'<li itemid="{role_id}" itemname="{name}">',
							'<div class="fixed">',
								'<div class="platforms"><tpl for="platforms.split(\',\')"><img src="/ui/images/icons/platform/{.}.png"></tpl></div>',
								'<div class="arch"><img src="/ui/images/icons/arch/{arch}.png"></div>',
							'</div>',
							'<b>{name}</b><br />',
							'<span>{os}</span><br /><br />',
							'<div class="info"><img class="add" src="/ui/images/ui/farms/allroles/add.png" style="cursor: pointer">&nbsp;<a href="#/roles/{role_id}/info"><img class="info" src="/ui/images/ui/farms/allroles/info.png" style="cursor: pointer"></a></div>',
						'</li>',
					'</tpl>',
				'</ul>',
				'</tpl>',
				'</div>',
			'</tpl>'
		],
		autoScroll: true,
		itemSelector: 'li',
		singleSelect: true,
		selectedItemCls: 'scalr-ui-farmselroles-selected',
		cls: 'scalr-ui-farmroleall',
		loadMask: false,

		collectData: function(records, startIndex) {
			var groups = [];
			for (key in this.groupsInfo) {
				var el = this.groupsInfo[key];
				groups[el.index] = { title: el.name, groupid: key, status: el.status, records: [] };
			}

			for (var i = 0, len = records.length; i < len; i++) {
				var index = this.groupsInfo[records[i].get('group')].index;
				groups[index].records[groups[index].records.length] = records[i].data;
			}

			return groups;
		},

		initScalrUI: function () {
			this.el.on('click', function (e) {
				var t = e.getTarget('li', 10, true), current = this.el.down('li.addrolesviewer-selected');
				if (current && (t && !t.hasCls('addrolesviewer-selected') || !t)) {
					current.removeCls('addrolesviewer-selected');
					var d = current.child('div.info');
					d.slideOut('t', {
						duration: 0.3
					});
				}

				if (t && !t.hasCls('addrolesviewer-selected')) {
					var ul = e.getTarget('ul', 10, true), offsets = t.getOffsetsTo(ul);

					t.addCls('addrolesviewer-selected');
					var d = t.child('div.info');
					d.setLeftTop(offsets[0], offsets[1] + 119);
					d.slideIn('t', {
						duration: 0.3
					});
				}

				var t = e.getTarget('img.add', 10, true), l = e.getTarget('li', 10, true);
				if (t) {
					var role = this.store.findRecord('role_id', l.getAttribute('itemid')), rLocations = role.get('locations'), me = this;

					var fireAddEvent = function (platform, location, role) {
						this.ownerCt.fireEvent('addrole', {
							role_id: role.get('role_id'),
							platform: platform,
							cloud_location: location,
							arch: role.get('arch'),
							generation: role.get('generation'),
							name: role.get('name'),
							behaviors: role.get('behaviors'),
							group: role.get('group'),
							tags: role.get('tags')
						});
					}

					var cnt = 0, plat = '', loc = '', platforms = {}, locations = {};
					for (var i in rLocations) {
						plat = i;
						platforms[i] = this.platforms[i];
					}

					if (Ext.Object.getSize(platforms) > 1) {
						plat = Ext.Object.getKeys(platforms)[0];
					} else {
						if (rLocations[plat].length == 1) {
							fireAddEvent.call(this, plat, rLocations[plat][0], role);
							return;
						}
					}

					if (rLocations[plat][0] != 'us-east-1') {
						for (var i = 1, len = rLocations[plat].length; i < len; i++) {
							if (rLocations[plat][i] == 'us-east-1') {
								rLocations[plat][i] = rLocations[plat][0];
								rLocations[plat][0] = 'us-east-1';
								break;
							}
						}
					}

					loc = rLocations[plat][0];
					for (var i = 0, len = rLocations[plat].length; i < len; i++)
						locations[rLocations[plat][i]] = me.platforms[plat]['locations'][rLocations[plat][i]];

					Scalr.Confirm({
						form: [{
							xtype: 'combo',
							store: {
								fields: [ 'id', 'name' ],
								proxy: 'object',
								data: platforms
							},
							fieldLabel: 'Platform',
							labelWidth: 50,
							editable: false,
							valueField: 'id',
							displayField: 'name',
							name: 'platform',
							value: plat,
							queryMode: 'local',
							anchor: '100%',
							listeners: {
								change: function () {
									var locations = [], plat = this.getValue();

									loc = rLocations[plat][0];
									for (var i = 0, len = rLocations[plat].length; i < len; i++)
										locations[rLocations[plat][i]] = me.platforms[plat]['locations'][rLocations[plat][i]];

									this.next('[name="location"]').store.load({ data: locations });
									this.next('[name="location"]').setValue(loc);
								}
							}
						}, {
							xtype: 'combo',
							store: {
								fields: [ 'id', 'name' ],
								proxy: 'object',
								data: locations
							},
							fieldLabel: 'Location',
							labelWidth: 50,
							allowBlank: false,
							editable: false,
							name: 'location',
							value: loc,
							valueField: 'id',
							displayField: 'name',
							queryMode: 'local',
							anchor: '100%',
							emptyText: 'Please select location'
						}],
						ok: 'Add',
						title: 'Add role "' + l.getAttribute('itemname') + '"',
						success: function (data) {
							fireAddEvent.call(this, data.platform, data.location, role);
						},
						scope: me
					});
				}
			}, this);
		},

		// always show groups
		refresh: function () {
			var me = this,
			el,
			records;

			if (!me.rendered || me.isDestroyed) {
				return;
			}

			me.fireEvent('beforerefresh', me);
			el = me.getTargetEl();
			records = me.store.getRange();

			el.update('');
			me.tpl.overwrite(el, me.collectData(records, 0));
			me.all.fill(Ext.query(me.getItemSelector(), el.dom));
			me.updateIndexes(0);

			me.selModel.refresh();
			me.hasSkippedEmptyText = true;
			me.fireEvent('refresh', me);

			// Upon first refresh, fire the viewready event.
			// Reconfiguring the grid "renews" this event.
			if (!me.viewReady) {
				// Fire an event when deferred content becomes available.
				// This supports grid Panel's deferRowRender capability
				me.viewReady = true;
				me.fireEvent('viewready', me);
			}
		},

		listeners: {
			refresh: function () {
				// add collapse links
				handler = function(e) {
					var el = e.getTarget("", 10, true).findParent("div.title", 10, true);
					var groupid = el.getAttribute("groupid");

					if (this.groupsInfo[groupid]) {
						this.groupsInfo[groupid].status = (this.groupsInfo[groupid].status == "contract") ? "" : "contract";
					}

					el.toggleCls("title-contract");
					var ul = el.next("ul");
					if (ul) {
						ul.toggleCls("hidden");
					}
				};

				Ext.select('#' + this.el.id + ' div.title').each(function(el) {
					if (! el.is("div.title-disabled")) {
						el.on('click', handler, this);
					}
				}, this);


			}
		}

	},

	dockedItems: [{
		xtype: 'toolbar',
		dock: 'top',
		layout: 'hbox',
		itemId: 'top',

		items: [{
			text: 'Filter',
			iconCls: 'scalr-ui-btn-icon-filter',
			menu: [{
					xtype: 'textfield',
					itemId: 'filter',
					emptyText: 'Filter ...',
					iconCls: 'no-icon'
				}, '-', {
					itemId: 'legacy',
					checked: false,
					text: 'Show legacy roles'
				}, {
					itemId: 'stable',
					checked: true,
					text: 'Stable'
				}, '-', {
					itemId: 'origin',
					xtype: 'combo',
					hideLabel: true,
					store: [ [ '', '' ], [ 'SHARED', 'Scalr' ], [ 'CUSTOM', 'Private' ] ],
					value: '',
					queryMode: 'local',
					editable: false,
					emptyText: 'Role origin...',
					iconCls: 'no-icon'
				}, {
					text: 'Platform',
					itemId: 'platform',
					menu: {}
				}]
			}]
		}],

	initComponent: function () {
		this.callParent();

		this.addEvents(
			'addrole'
		);

		this.view = this.add(Ext.create('Ext.view.View', this.view));
	},

	onRender: function () {
		var me = this, toolbar = this.down('#top');

		me.callParent(arguments);
		me.on('show', function () {
			Scalr.Request({
				processBox: {
					type: 'action'
				},
				url: '/roles/xGetList',
				params: this.loadParams,
				success: function (data) {
					me.platforms = me.view.platforms = data.platforms;
					me.platformFilter = {};

					for (var i in data.platforms) {
						me.down('#platform').menu.add({
							text: data.platforms[i].name,
							platform: data.platforms[i].id,
							checked: true,
							hideOnClick: false,
							handler: me.changeSelectedPlatforms,
							scope: me
						});
						me.platformFilter[i] = true;
					}

					me.view.groupsInfo = {};
					var i = 0;
					for (var key in data.groups)
						me.view.groupsInfo[key] = { status: "contract", name: data.groups[key], index: i++ };

					me.view.store.load({ data: data.roles });
					me.view.initScalrUI.call(me.view);

					toolbar.down('#legacy').on('checkchange', me.filterRoles, me);
					toolbar.down('#stable').on('checkchange', me.filterRoles, me);
					toolbar.down('#origin').on('select', me.filterRoles, me);
					toolbar.down('#filter').on('change', me.filterRoles, me);

					me.filterRoles();
				}
			});
		}, this, { single: true });
	},

	changeSelectedPlatforms: function (comp) {
		this.platformFilter[comp.platform] = comp.checked;
		this.filterRoles();
	},

	filterRoles: function() {
		var filters = [], toolbar = this.down('#top');

		filters[filters.length] = new Ext.util.Filter({
			filterFn: Ext.Function.bind(function (record) {
				return record.get('isstable') == this.checked;
			}, toolbar.down('#stable'))
		});

		filters[filters.length] = new Ext.util.Filter({
			filterFn: Ext.Function.bind(function (record) {
				return this.checked || record.get('generation') == 2;
			}, toolbar.down('#legacy'))
		});

		if (toolbar.down('#origin').getValue())
			filters[filters.length] = new Ext.util.Filter({
				filterFn: Ext.Function.bind(function (record) {
					return this.getValue() == record.get('origin');
				}, toolbar.down('#origin'))
			});

		filters[filters.length] = new Ext.util.Filter({
			filterFn: Ext.Function.bind(function (record) {
				var locations = record.get('locations');
				for (var key in this.platformFilter) {
					if (this.platformFilter[key] && locations[key]) {
						return true;
						var loc = locations[key];
						for (var i = 0, len = loc.length; i < len; i++) {
							if (this.locationFilter[loc[i]])
								return true;
						}
					}
				}
				return false;
			}, this)
		});

		if (toolbar.down('#filter').getValue())
			filters[filters.length] = new Ext.util.Filter({
				filterFn: Ext.Function.bind(function (record) {
					return (record.get('name').toLowerCase().search(this.getValue().toLowerCase()) != -1) ? true : false;
				}, toolbar.down('#filter'))
			});

		this.view.store.clearFilter(true);
		this.view.store.filter(filters);
	}


});

/*


		this.dataView = new Ext.DataView({
			roles: this.roles,



			refresh: function() {
				this.clearSelections(false, true);
				var el = this.getTemplateTarget();
				el.update("");
				var records = this.store.getRange();

				// always show groups
				this.tpl.overwrite(el, this.collectData(records, 0));
				this.all.fill(Ext.query(this.itemSelector, el.dom));
				this.updateIndexes(0);

				// update links
				this.addCollapseLinks();
			},

			addCollapseLinks: function() {

			},

		});



		this.dataView.on('afterrender', this.dataView.filterRoles, this);




				}
			}, this);
		}, this);

		this.items = [this.dataView];

		Scalr.Viewers.AllRolesViewer.superclass.initComponent.call(this);

	}
});
*/
