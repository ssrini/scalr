Scalr.regPage('Scalr.ui.services.chef.servers.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'id', 'username', 'url'
		],
		proxy: {
			type: 'scalr.paging',
			url: '/services/chef/servers/xListServers/'
		},
		remoteSort: true
	});
	return Ext.create('Ext.grid.Panel', {
		title: 'Chef &raquo; Servers &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-chef-servers-view',
		plugins: {
			ptype: 'gridstore'
		},
		viewConfig: {
			deferEmptyText: false,
			emptyText: 'No Servers found'
		},

		columns: [
			{ text: "URL", flex: 3, dataIndex: 'url', sortable: true },
			{ text: "User name", width: 220, dataIndex: 'username', sortable: true },
			{ xtype: 'optionscolumn',
				optionsMenu: [{ 
					text:'Edit', 
					iconCls: 'scalr-menu-icon-edit',
					menuHandler: function(item) {
						Scalr.event.fireEvent('redirect','/#/services/chef/servers/edit?servId=' + item.record.get('id'));
					}
				},{
					xtype: 'menuseparator',
					itemId: 'option.attachSep'
				},{ 
					text:'Delete', 
					iconCls: 'scalr-menu-icon-delete',
					menuHandler: function(item) {
						Scalr.Request({
							confirmBox: {
								msg: 'Remove selected chef server ?',
								type: 'delete'
							},
							processBox: {
								msg: 'Removing selected chef server... Please wait, it can take a few minutes.',
								type: 'delete'
							},
							scope: this,
							url: 'services/chef/servers/xDeleteServer',
							params: {servId: item.record.get('id')},
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
			items:['-',{
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Add new chef server',
				listeners: {
					click: function() {
						Scalr.event.fireEvent('redirect','/#/services/chef/servers/create');
					}
				}
			}]
		}]
	});
});