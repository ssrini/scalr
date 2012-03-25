Scalr.regPage('Scalr.ui.services.apache.vhosts.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [ 'id','env_id','client_id','name','role_behavior','is_ssl_enabled','last_modified','farm_name','role_name', 'farm_id', 'farm_roleid'],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/services/apache/vhosts/xListVhosts/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Services &raquo; Apache &raquo; Virtualhosts',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { presetId: '' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-services-apache-vhosts-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: "No apache virtualhosts found"
		},

		columns:[
			{ header: "ID", width: 50, dataIndex: 'id', sortable:true },
			{ header: "Name", flex: 5, dataIndex: 'name', sortable:true },
			{ header: "Farm & Role", flex: 5, dataIndex: 'farm_id', sortable: true, xtype: 'templatecolumn', tpl:
				'<tpl if="farm_id">'+
					'<a href="#/farms/{farm_id}/view" title="Farm {farm_name}">{farm_name}</a>' +
					'&nbsp;&rarr;&nbsp;<a href="#/farms/{farm_id}/roles/{farm_roleid}/view" title="Role {role_name}">{role_name}</a> ' +
				'</tpl>' +
				'<tpl if="! farm_id"><img src="/ui/images/icons/false.png" /></tpl>'
			},
			{ header: "Last time modified", width: 150, dataIndex: 'last_modified', sortable: false },
			{ header: "SSL", width: 40, dataIndex: 'is_ssl_enabled', sortable: true, xtype: 'templatecolumn', tpl:
				'<tpl if="is_ssl_enabled == 1"><img src="/ui/images/icons/true.png" /></tpl>' +
				'<tpl if="is_ssl_enabled == 0"><img src="/ui/images/icons/false.png" /></tpl>'
			},
			{
				xtype: 'optionscolumn',
				optionsMenu: [{
					text: 'Edit',
					href: "#/services/apache/vhosts/{id}/edit"
				}]
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
						msg: 'Remove selected apache virtualhost(s) ?'
					},
					processBox: {
						type: 'delete',
						msg: 'Removing apache virtualhost(s). Please wait...'
					},
					url: '/services/apache/vhosts/xRemove/',
					dataHandler: function(records) {
						var vhosts = [];
						for (var i = 0, len = records.length; i < len; i++) {
							vhosts[vhosts.length] = records[i].get('id');
						}
						return { vhosts: Ext.encode(vhosts) };
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
					iconCls: 'no-icon',
					store: store
				}]
			}, '-', {
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Create new virtualhost',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/services/apache/vhosts/create');
				}
			}]
		}]
	});
});
