Scalr.regPage('Scalr.ui.dnszones.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			{ name: 'id', type: 'int' },
			{ name: 'client_id', type: 'int' },
			'zone_name', 'status', 'role_name', 'farm_roleid', 'dtlastmodified', 'farm_id', 'farm_name'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/dnszones/xListZones/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'DNS Zones &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { dnsZoneId: '', clientId: '', farmId: '', farmRoleId: ''};
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		tools: [{
			type: 'video',
			handler: function () {
				window.open('http://youtu.be/CckXS9OSYx8?t=7s');
			}
		}],
		store: store,
		stateId: 'grid-dnszones-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No DNS zones found'
		},

		columns: [
			{ text: "Domain name", flex: 2, dataIndex: 'zone_name', sortable: true, xtype: 'templatecolumn',
				tpl: '<a target="_blank" href="http://{zone_name}">{zone_name}</a>'
			},
			{ text: "Assigned to", flex: 1, dataIndex: 'role_name', sortable: false, xtype: 'templatecolumn', tpl:
				'<tpl if="farm_id &gt; 0"><a href="#/farms/{farm_id}/view" title="Farm {farm_name}">{farm_name}</a>' +
					'<tpl if="farm_roleid &gt; 0">&nbsp;&rarr;&nbsp;<a href="#/farms/{farm_id}/roles/{farm_roleid}/view" ' +
					'title="Role {role_name}">{role_name}</a></tpl>' +
				'</tpl>' +
				'<tpl if="farm_id == 0"><img src="/ui/images/icons/false.png" /></tpl>'
			},
			{ text: "Last modified", width: 200, dataIndex: 'dtlastmodified', sortable: true, xtype: 'templatecolumn',
				tpl: '<tpl if="dtlastmodified">{dtlastmodified}</tpl><tpl if="! dtlastmodified">Never</tpl>'
			},
			{ text: "Status", width: 150, dataIndex: 'status', sortable: false, xtype: 'templatecolumn', tpl:
				new Ext.XTemplate('<span style="{[this.getClass(values.status)]}">{status}</span>', {
					getClass: function (value) {
						if (value == 'Active')
							return "color: green";
						else if (value == 'Pending create' || value == 'Pending update')
							return "color: #666633";
						else
							return "color: red";
					}
				})
			}, {
				xtype: 'optionscolumn',
				optionsMenu: [
					{ text:'Edit DNS Zone', href: "#/dnszones/{id}/edit" },
					{ text: 'Settings', href: "#/dnszones/{id}/settings" }
				],
				getVisibility: function (record) {
					return (record.get('status') != 'Pending delete' && record.get('status') != 'Pending create');
				}
			}
		],

		multiSelect: true,
		selModel: {
			selType: 'selectedmodel',
			selectedMenu: [{
				text: 'Delete',
				iconCls: 'scalr-menu-icon-delete',
				request: {
					confirmBox: {
						type: 'delete',
						msg: 'Remove selected dns zone(s): %s ?'
					},
					processBox: {
						type: 'delete',
						msg: 'Removing dns zone(s). Please wait...'
					},
					url: '/dnszones/xRemoveZones',
					dataHandler: function(records) {
						var zones = [];
						this.confirmBox.objects = [];
						for (var i = 0, len = records.length; i < len; i++) {
							zones.push(records[i].get('id'));
							this.confirmBox.objects.push(records[i].get('zone_name'));
						}
						return { zones: Ext.encode(zones) };
					}
				}
			}]
		},

		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top',
			items: [ '-', {
				text: 'Filter',
				iconCls: 'scalr-ui-btn-icon-filter',
				menu: [{
					xtype: 'tbfilterfield',
					store: store,
					iconCls: 'no-icon',
					emptyText: 'Filter'
				}]
			}, '-', {
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Create new DNS Zone',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/dnszones/create');
				}
			}, '-', {
				text: 'Default Records',
				tooltip: 'Manage Default DNS records',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/dnszones/defaultRecords');
				}
			}]
		}]
	});
});
