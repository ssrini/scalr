Scalr.regPage('Scalr.ui.tools.aws.s3.buckets', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [ 'name' , 'farmId', 'farmName', 'cfid', 'cfurl', 'cname', 'status', 'enabled'],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/tools/aws/s3/xListBuckets/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Tools &raquo; Amazon Web Services &raquo; S3 &raquo; Buckets &amp; Cloudfront',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
				this.store.load();
		},
		store: store,
		stateId: 'grid-tools-aws-s3-buckets',
		plugins: {
			ptype: 'gridstore'
		},
		viewConfig: {
			emptyText: "No buckets found"
		},
		columns: [
			{ header: "Bucket name", flex: 2, dataIndex: 'name', sortable: false },
			{ header: "Used by", flex: 1, dataIndex: 'farmId', xtype: 'templatecolumn', sortable: false, tpl:
				'<tpl if="farmId"><a href="#/farms/{farmId}/view">{farmName}</a></tpl>' +
				'<tpl if="! farmId"><img src="/ui/images/icons/false.png"></tpl>'
			},
			{ header: "Cloudfront ID", flex: 2, dataIndex: 'cfid', sortable: false},
			{ header: "Cloudfront URL", flex: 2, dataIndex: 'cfurl', sortable: false},
			{ header: "CNAME", flex: 3, dataIndex: 'cname', sortable: false},
			{ header: "Status", width: 80, dataIndex: 'status', sortable: false},
			{ header: "Enabled", width: 80, dataIndex: 'enabled', xtype: 'templatecolumn', sortable: false, tpl:
				'<tpl if="enabled == \'true\'"><img src="/ui/images/icons/true.png"></tpl>' +
				'<tpl if="enabled == \'false\' || !enabled"><img src="/ui/images/icons/false.png"></tpl>'
		},{
			xtype: 'optionscolumn',
			width: 120,
			getOptionVisibility: function (item, record) {
				switch (item.itemId) {
					case "option.disable_dist":
						return ((record.data.enabled == "true") && record.data.cfid);
	
					case  "option.enable_dist":
						return ((record.data.enabled == "false") && record.data.cfid);
	
					case "option.delete_dist":
						return (record.data.cfid);
	
					case "option.create_dist":
							return (!record.data.cfid);
	
					default:
						return true;
				}
			},
			optionsMenu: [
			{ itemId: "option.create_dist",  text: 'Create distribution', href: "#/tools/aws/s3/manageDistribution?bucketName={name}"},
			{ itemId: "option.delete_dist", iconCls: 'scalr-menu-icon-delete', text: 'Remove distribution',
				menuHandler: function(item) {
					Scalr.Request({
						confirmBox: {
							msg: 'Remove distribution ?',
							type: 'delete'
						},
						processBox: {
							msg: 'Removing selected distribution... Please wait, it can take a few minutes.',
							type: 'delete'
						},
						scope: this,
						url: '/tools/aws/s3/xDeleteDistribution',
						params: {id: item.record.get('cfid'), cfurl: item.record.get('cfurl'), cname: item.record.get('cname')},
						success: function (data, response, options){
							store.load();
						}
					});
				}
			},
			{ itemId: "option.disable_dist", text: 'Disable distribution',
				menuHandler: function(item) {
					Scalr.Request({
						processBox: {
							type: 'action'
						},
						scope: this,
						url: '/tools/aws/s3/xUpdateDistribution',
						params: {id: item.record.get('cfid'), enabled: false},
						success: function (data, response, options){
							store.load();
						}
					});
				}
			},
			{ itemId: "option.enable_dist", text: 'Enable distribution',
				menuHandler: function(item) {
					Scalr.Request({
						processBox: {
							type: 'action'
						},
						scope: this,
						url: '/tools/aws/s3/xUpdateDistribution',
						params: {id: item.record.get('cfid'), enabled: true},
						success: function (data, response, options){
							store.load();
						}
					});
				}
			},
				new Ext.menu.Separator({itemId: "option.editSep"}),
			{ itemId: "option.delete_backet", iconCls: 'scalr-menu-icon-delete', text: 'Delete bucket', 
				menuHandler: function(item) {
					if(item.record.get('cfid')) {
						Scalr.utils.CreateMsgPanel({
							title: 'Information',
							width: 300,
							layout: {
								type: 'hbox',
								pack: 'center'
							},
							items: [{
								xtype: 'displayfield',
								value: 'Remove distribution before deleting'
							}],
							zIndex: 18000,
							dockedItems: [{
								xtype: 'container',
								dock: 'bottom',
								cls: 'scalr-ui-docked-bottombar',
								layout: {
									type: 'hbox',
									pack: 'center'
								},
								items: [{
									xtype: 'button',
									text: 'OK',
									width: 80,
									itemId: 'buttonOk',
									handler: function () {
										this.up('#panel').close();
									}
								}]
							}]
						});
					}
					else {
						Scalr.Request({
							confirmBox: {
								msg: 'Remove selected bucket ?',
								type: 'delete'
							},
							processBox: {
								msg: 'Removing selected bucket... Please wait, it can take a few minutes.',
								type: 'delete'
							},
							scope: this,
							url: '/tools/aws/s3/xDeleteBucket',
							params: {bucketName: item.record.get('name')},
							success: function (data, response, options){
								store.load();
							}
						});
					}
					
				}
			}]
		}], 
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
				}]
			}, {
				xtype: 'tbfilterinfo'
			}, '-',{
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Create new bucket',
				handler: function() {
					Scalr.Request({
						confirmBox: {
							title: 'Create new Bucket',
							form: [{
								xtype: 'combo',
								name: 'location',
								fieldLabel: 'Select location',
								width: 303,
								editable: false,
								allowBlank: false,
								queryMode: 'local',
								name: 'zone',
								store: {
									fields: [ 'id', 'name' ],
									data: moduleParams.locations,
									proxy: 'object'
								},
								valueField: 'id',
								displayField: 'name'
							},{
								xtype: 'textfield',
								name: 'bucketName',
								fieldLabel: 'Bucket Name',
								allowBlank: false,
								width: 303,
							}],						
							formValidate: true,
							ok: 'Add',
						},
						processBox: {
							msg: 'Creating new Bucket...',
							type: 'save'
						},
						scope: this,
						url: '/tools/aws/s3/xCreateBucket',
						success: function (data, response, options){
							store.load();
						}
					});
				}
			}]
		}]
	});
});