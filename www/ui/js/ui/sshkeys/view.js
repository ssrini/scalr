Scalr.regPage('Scalr.ui.sshkeys.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [ 'id','type','fingerprint','cloud_location','farm_id','cloud_key_name' ],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/sshkeys/xListSshKeys/'
		},
		remoteSort: true
	});


	return Ext.create('Ext.grid.Panel', {
		title: 'Tools &raquo; SSH Keys manager',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { sshKeyId: '' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-sshkeys-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No SSH keys found'
		},

		columns: [
			{ text: 'Key ID', width: 100, dataIndex: 'id', sortable: true },
			{ text: 'Name', flex: 1, dataIndex: 'cloud_key_name', sortable: true },
			{ header: 'Type', width: 200, dataIndex: 'type', sortable: true },
			{ header: 'Cloud location', width: 150, dataIndex: 'cloud_location', sortable: true },
			{ header: 'Farm ID', width: 80, dataIndex: 'farm_id', sortable: true },
			{
				xtype: 'optionscolumn',
				optionsMenu: [{
					text: 'Download Private key',
					menuHandler: function (item) {
 						Scalr.utils.UserLoadFile('/sshkeys/' + item.record.get('id') + '/downloadPrivate');
 					}
 				}, {
	 				text: 'Download SSH public key',
	 				menuHandler: function (item) {
 						Scalr.utils.UserLoadFile('/sshkeys/' + item.record.get('id') + '/downloadPublic');
 					}
 				}]
			/*
			new Ext.menu.Separator({itemId: "option.download_sep"}),
			{ itemId: "option.regenerate", text:'Regenerate', handler: function(item) {

				Ext.Msg.wait('Please wait while generating keys');
				Ext.Ajax.request({
					url: '/sshkeys/regenerate',
					params:{id:item.currentRecordData.id},
					success: function(response, options) {
						Ext.MessageBox.hide();

						var result = Ext.decode(response.responseText);
						if (result.success == true) {
							Scalr.Viewers.SuccessMessage('Key successfully regenerated');
						} else {
							Scalr.Viewers.ErrorMessage(result.error);
						}
					}
				});
			}}
			*/
			}
		],

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
			}]
		}]
	});
});
