Scalr.regPage('Scalr.ui.services.mongodb.status', function (loadParams, moduleParams) {
	var clusterStatus = false;
	if(moduleParams['mongodb']['status'] == 'active');
		clusterStatus = true;
	var serverStatus = false;
	if(moduleParams['mongodb']['map'][0][0])
		serverStatus = moduleParams['mongodb']['map'][0][0];
	var logsStore = Ext.create('store.store', {
		fields: [
			'id', 'dtadded', 'severity', 'message'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/services/mongodb/xGetClusterLog/'
		},
		remoteSort: true
	});
	
	var panel = Ext.create('Ext.form.Panel', {
		width: 700,
		title: 'MongoDB status',
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		fieldDefaults: {
			anchor: '100%',
			labelWidth: 130
		},
		items: [{
			xtype: 'fieldset',
			title: 'MongoDB access credentials',
			items: [{
				xtype: 'displayfield',
				fieldLabel: 'Login',
				value: 'scalr'
			}, {
				xtype: 'displayfield',
				fieldLabel: 'Password',
				value: (moduleParams['mongodb']['password']) ? moduleParams['mongodb']['password'] : '<span style="color:red;">MongoDB is not initialized yet</span>'
			}, {
				xtype: 'displayfield',
				hidden: !(moduleParams['mongodb']['status']),
				fieldLabel: 'Cluster status',
				value: moduleParams['mongodb']['status']
			}]
		}, {
			xtype: 'fieldset',
			title: 'DNS endpoints',
			items: [{
				'cls': 'scalr-ui-form-field-info',
				'html': 'Public - To connect to the service from the Internet<br / >Private - To connect to the service from another instance',
				'border': false
			}, {
				xtype: 'displayfield',
				labelWidth: 200,
				fieldLabel: 'Public endpoint',
				value: 'ext.mongo.' + moduleParams['farmHash'] + '.scalr-dns.net'
			}, {
				xtype: 'displayfield',
				labelWidth: 200,
				fieldLabel: 'Private endpoint',
				value: 'int.mongo.' + moduleParams['farmHash'] + '.scalr-dns.net'
			}]
		}, {
			xtype:'fieldset',
			title: 'Cluster map',
			items:[{
				xtype: 'component',
				renderData: moduleParams['mongodb'],
				padding: {
					left: 5,
					right: 5,
					top: 10,
					bottom: 10
				},
				listeners: {
					afterrender: function () {
						this.el.on('click', function (e) {
							if (e.getTarget('div.scalr-ui-services-mongodb-status-shard-add') && (clusterStatus && (serverStatus && serverStatus != 'terminated')))
								Scalr.Request({
									confirmBox: {
										type: 'action',
										msg: 'Are you sure want to add new shard to your mongodb cluster?'
									},
									processBox: {
										type: 'action',
										msg: 'Adding new shard to mongodb cluster. Please wait...'
									},
									url: '/services/mongodb/xAddShard/',
									params: {farmId: loadParams['farmId'], farmRoleId: moduleParams['farmRoleId']},
									success: function() {

									}
								});
							else if (e.getTarget('div.scalr-ui-services-mongodb-status-replica-add') && (clusterStatus && (serverStatus && serverStatus != 'terminated')))
								Scalr.Request({
									confirmBox: {
										type: 'action',
										msg: 'Are you sure want to add new replica to each shard on your mongodb cluster?'
									},
									processBox: {
										type: 'action',
										msg: 'Adding new replica to each shard on mongodb cluster. Please wait...'
									},
									url: '/services/mongodb/xAddReplicaSet/',
									params: {farmId: loadParams['farmId'], farmRoleId: moduleParams['farmRoleId']},
									success: function(){

									}
								});
						});
					}
				},
				renderTpl: new Ext.XTemplate(
                        '<tpl for="map">' +
                            '<div class="scalr-ui-services-mongodb-status-shard">' +
                                '<div class="scalr-ui-services-mongodb-status-name">Shard #{[xindex-1]}</div>' +
                                '<tpl for="values">' +
                                    '<div class="scalr-ui-services-mongodb-status-replica">' +
                                        '<div title="{[this.getTitle(values)]}" class="scalr-ui-services-mongodb-status-replica-icon scalr-ui-services-mongodb-status-replica-{.}"></div>' +
                                    '</div>' +
                                    '<div class="{[ xindex < xcount ? "scalr-ui-services-mongodb-status-vdelim" : "x-hide-display"]}"></div>' +
                                '</tpl>' +
                            '</div>' +
                            '<div class="{[ xindex < xcount ? "scalr-ui-services-mongodb-status-delim" : "x-hide-display"]}">' +
                                '<tpl for="values">' +
                                    '<div class="scalr-ui-services-mongodb-status-hdelim"></div>' +
                                '</tpl>' +
                            '</div>' +
                        '</tpl>' +
                        '<div class=<tpl if="!this.serverStat() || !status">"scalr-ui-services-mongodb-status-add-disable scalr-ui-services-mongodb-status-shard-add"</tpl><tpl if="status&&this.serverStat()">"scalr-ui-services-mongodb-status-add scalr-ui-services-mongodb-status-shard-add"</tpl> title="Add new shard" style="height: {[ 33 + values.map[0].length*50 + 25*(values.map[0].length - 1) + 15]}px"></div>' +
                        '<div class="x-clear"></div>' +
                        '<div class=<tpl if="!this.serverStat() || !status">"scalr-ui-services-mongodb-status-add-disable scalr-ui-services-mongodb-status-replica-add"</tpl><tpl if="status&&this.serverStat()">"scalr-ui-services-mongodb-status-add scalr-ui-services-mongodb-status-replica-add"</tpl> title="Add new replica set" style="width: {[ 80*values.map.length + 19*(values.map.length - 1) ]}px"></div>' +
                    '<div class="x-clear"></div>',
                    {
                        getTitle: function (value) {
                            if(value == 'terminated')
                                return 'Not running';
                            else
                                return value;
                        },
                        serverStat: function () {
                        	if (!serverStatus || serverStatus == 'terminated')
                        		return false;
                        	else 
                        		return true;
                        }
                    })
			}]
		}, {
            xtype:'fieldset',
            title: 'Actions',
            items:[{
                xtype: 'button',
                text: 'Remove shard',
                width: 120,
                margin: {
                    bottom: 5
                },
                handler: function(){
                    Scalr.Request({
                        confirmBox: {
                            type: 'action',
                            msg: 'Are you sure want to remove shard from your mongodb cluster?'
                        },
                        processBox: {
                            type: 'action',
                            msg: 'Initiating shard removal. Please wait...'
                        },
                        url: '/services/mongodb/xRemoveShard/',
                        params: {farmId: loadParams['farmId'], farmRoleId: moduleParams['farmRoleId']},
                        success: function(){

                        }
                    });
                }
            }, {
                xtype: 'button',
                margin: {
                    left:5,
                    bottom: 5
                },
                text: 'Remove replica from each shard',
                width: 200,
                handler: function(){
                    Scalr.Request({
                        confirmBox: {
                            type: 'action',
                            msg: 'Are you sure want to remove replica set from your mongodb cluster?'
                        },
                        processBox: {
                            type: 'action',
                            msg: 'Initiating replica set removal. Please wait...'
                        },
                        url: '/services/mongodb/xRemoveReplicaSet/',
                        params: {farmId: loadParams['farmId'], farmRoleId: moduleParams['farmRoleId']},
                        success: function(){

                        }
                    });
                }
            }, {
                xtype:'button',
                margin: {
                    left:5,
                    bottom: 5
                },
                width: 120,
                text:'<span style="color:red;">Shutdown cluster</span>',
                handler: function(){
                    Scalr.Request({
                        confirmBox: {
                            type: 'action',
                            msg: 'Are you sure want to shutdown your mongodb cluster?'
                        },
                        processBox: {
                            type: 'action',
                            msg: 'Initiating cluster shutdown. Please wait...'
                        },
                        url: '/services/mongodb/xTerminate/',
                        params: {farmId: loadParams['farmId'], farmRoleId: moduleParams['farmRoleId']},
                        success: function(){

                        }
                    });
                }
            }]
        },/* {
			xtype: 'fieldset',
			title: 'Data bundles',
			items: [{
				xtype: 'fieldcontainer',
				fieldLabel: 'Last data bundle',
				layout: 'hbox',
				items: [{
					xtype: 'displayfield',
					name: 'dataBundleStatus',
					width: 200,
					value: (moduleParams['isBundleRunning'] == 1) ? 'In progress...' : moduleParams['dtLastBundle']
				}, {
					xtype: 'button',
					margin: {
						left:3
					},
					text: 'Create data bundle',
					handler: function(){
						Scalr.Request({
							confirmBox: {
								type: 'action',
								msg: 'Are you sure want to create data bundle?'
							},
							processBox: {
								type: 'action',
								msg: 'Sending data bundle request to the server. Please wait...'
							},
							url: '/services/mongodb/xCreateDataBundle/',
							params: {farmId: loadParams['farmId'], farmRoleId: moduleParams['farmRoleId']},
							success: function(){
								panel.down('[name="dataBundleStatus"]').setValue("In progress...");
							}
						});
					}
				}]
			}]
		}, */{
			xtype: 'gridpanel',
			store: logsStore,
			height: 300,
			plugins: {
				ptype: 'gridstore'
			},
			margin: {
				bottom: 5
			},
			enableColumnMove: false,
			viewConfig: {
				deferEmptyText: false,
				emptyText: "Log is empty"
			},
			flex: 2,
			title: 'Cluster log',
			itemId: 'users',
			multiSelect: true,
			columns: [{
				text: 'Date',
				dataIndex: 'dtadded',
				width: 160
			}, {
				text: 'Severity',
				dataIndex: 'severity',
				width: 80
			}, {
				text: 'Message',
				dataIndex: 'message',
				flex: 1
			}],
			dockedItems: [{
				xtype: 'scalrpagingtoolbar',
				store: logsStore,
				dock: 'top',
				items: ['-', {
					text: 'Filter',
					iconCls: 'scalr-ui-btn-icon-filter',
					menu: [{
						xtype: 'tbfilterfield',
						store: logsStore,
						iconCls: 'no-icon',
						emptyText: 'Filter'
					}]
				}]
			}]
		}],
		tools: [{
			type: 'refresh',
			handler: function () {
				Scalr.event.fireEvent('refresh');
			}
		}, {
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}]
	});
    
	return panel;
});
