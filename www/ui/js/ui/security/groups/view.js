Scalr.regPage('Scalr.ui.security.groups.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'name', 'description', 'id'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/security/groups/xListGroups/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Security &raquo; Groups &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = {  };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-security-groups-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: "No security groups found"
		},

		columns: [
			{ header: "Name", flex: 1, dataIndex: 'name', sortable: true },
			{ header: "Description", flex: 2, dataIndex: 'description', sortable: true },
			{
				xtype: 'optionscolumn',
				optionsMenu: [
					{ itemId: "option.edit", iconCls: 'scalr-menu-icon-edit', text:'Edit', menuHandler:function(item) {			
						Scalr.event.fireEvent('redirect', '#/security/groups/' + item.record.get('id') + '/edit?platform=' + loadParams['platform'] + '&cloudLocation=' + store.proxy.extraParams.cloudLocation);
					} }
				],

				getOptionVisibility: function (item, record) {
					return true;
				},

				getVisibility: function (record) {
					return true;
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
						msg: 'Remove selected security group(s)?',
						type: 'delete'
					},
					processBox: {
						msg: 'Removing selected security group(s)... Please wait, it can take a few minutes.',
						type: 'delete'
					},
					url: '/security/groups/xRemove',
					dataHandler: function (records) {
						var groups = [];
						for (var i = 0, len = records.length; i < len; i++) {
							groups[groups.length] = records[i].get('id');
						}

						return { groups: Ext.encode(groups), platform:loadParams['platform'], cloudLocation: store.proxy.extraParams.cloudLocation};
					}
				}
			}]
		},

		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top',
			items: ['-', {
				text: 'Filter',
				iconCls: 'scalr-ui-btn-icon-filter',
				menu: [{
					xtype: 'tbfilterfield',
					store: store,
					iconCls: 'no-icon'
				}, {
					xtype: 'fieldcloudlocation',
					itemId: 'cloudLocation',
					store: {
						fields: [ 'id', 'name' ],
						data: moduleParams.locations,
						proxy: 'object'
					},
					gridStore: store,
					cloudLocation: loadParams['cloudLocation'] || ''
				}, {
					text: 'Show all security groups',
					checked: false,
					checkHandler: function (field, checked) {
						store.proxy.extraParams.showAll = checked ? 1 : 0;
						store.loadPage(1);
					}
				}]
			}]
		}]
	});
});
