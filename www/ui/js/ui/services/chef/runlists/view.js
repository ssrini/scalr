Scalr.regPage('Scalr.ui.services.chef.runlists.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'id', 'name', 'description', 'servId', 'chefEnv'
		],
		proxy: {
			type: 'scalr.paging',
			url: '/services/chef/runlists/xListRunlists/'
		},
		remoteSort: true
	});
	return Ext.create('Ext.grid.Panel', {
		title: 'Chef &raquo; Runlists &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-chef-runlists-view',
		plugins: {
			ptype: 'gridstore'
		},
		viewConfig: {
			deferEmptyText: false,
			emptyText: 'No Runlists found'
		},

		columns: [
			{ text: "Name", width: 220, dataIndex: 'name', sortable: true },
			{ text: "Description", flex: 3, dataIndex: 'description', sortable: true },
			{
				text: 'Chef environment',
				flex: 2,
				dataIndex: 'chefEnv'
			},{ xtype: 'optionscolumn',
				optionsMenu: [{ 
					text: 'Edit', 
					iconCls: 'scalr-menu-icon-edit',
					menuHandler: function(item) {
						Scalr.event.fireEvent('redirect','#/services/chef/runlists/edit?runlistId=' + item.record.get('id'));
					}
				},{
					xtype: 'menuseparator',
					itemId: 'option.attachSep'
				},{ 
					text:'Source', 
					iconCls: 'scalr-menu-icon-info',
					menuHandler: function(item) {
						Scalr.event.fireEvent('redirect','#/services/chef/runlists/source?runlistId=' + item.record.get('id'));
					}
				},{
					xtype: 'menuseparator',
					itemId: 'option.attachSep'
				},{ 
					text: 'Delete', 
					iconCls: 'scalr-menu-icon-delete',
					menuHandler: function(item) {
						Scalr.Request({
							confirmBox: {
								msg: 'Remove selected runlist ?',
								type: 'delete'
							},
							processBox: {
								msg: 'Removing selected runlist... Please wait, it can take a few minutes.',
								type: 'delete'
							},
							scope: this,
							url: 'services/chef/runlists/xDeleteRunlist',
							params: {runlistId: item.record.get('id'), runlistName: item.record.get('name'), servId: item.record.get('servId'), chefEnv: item.record.get('chefEnv')},
							success: function (data, response, options){
								store.load();
							}
						});
					}
				}],
				getVisibility: function (record) {
					return true;
				}
			}],
		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top',
			items:[ '-', {
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Add new runlist',
				listeners: {
					click: function() {
						Scalr.event.fireEvent('redirect','#/services/chef/runlists/create');
					}
				}
			}]
		}]
	});
});