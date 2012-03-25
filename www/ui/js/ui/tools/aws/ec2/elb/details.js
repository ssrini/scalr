Scalr.regPage('Scalr.ui.tools.aws.ec2.elb.details', function(loadParams, moduleParams) {
	var elb = moduleParams.elb;
	var healthCheck = elb.HealthCheck;
	var listenerDescription = elb.ListenerDescriptions;
	var instanceString = '';
	var availableZones = '';
	var policyFlag = true;
	if(elb.AvailabilityZones){
		availableZones = elb.AvailabilityZones.join(', ');
	}
	else availableZones = "There are no availability zones registered on this load balancer";
	if (elb.Instances) {
		Ext.each(elb.Instances, function(item){
			instanceString += " <a href='#/tools/aws/ec2/elb/" + elb['LoadBalancerName']+ "/instanceHealth?awsInstanceId=" + item.InstanceId + "&cloudLocation="+ loadParams['cloudLocation'] +"' style = 'cursor: pointer; text-decoration: none;'>" + item.InstanceId + "</a>";
		});
		/*for (i = 0; i < elb.Instances.length; i++) {
				if (i != 0) instanceString += ', ';
				instanceString += "<a href='#/tools/aws/ec2/elb/" + elb['LoadBalancerName']+ "/instanceHealth?awsInstanceId=" + elb.Instances[i].InstanceId + "&cloudLocation="+ loadParams['cloudLocation'] +"' style = 'cursor: pointer; text-decoration: none;'>" + elb.Instances[i].InstanceId + "</a>";
		}*/
	}
	else instanceString = "There are no instances registered on this load balancer";
	
	var policyStore = Ext.create('Ext.data.JsonStore', {
		fields: [
			{ name: 'PolicyType' },
			{ name: 'PolicyName' },
			{ name: 'CookieSettings' }
		],
		data: elb.Policies
	});
	
	var comboStore = Ext.create('Ext.data.JsonStore', {
		fields: [{name: 'PolicyName', name: 'description'}],
		data: [{'PolicyName' : '','description' : 'Do not use session stickness on this ELB port'}]
	});
	Ext.each(policyStore.getRange(), function(item){
		comboStore.add({PolicyName: item.get('PolicyName'), description: item.get('PolicyName')});
	});
	
	var listenerStore = Ext.create('Ext.data.JsonStore', {
		fields: [
			{ name: 'Protocol' },
			{ name: 'LoadBalancerPort' },
			{ name: 'InstancePort' },
			{ name: 'PolicyNames' }
		]
	});	
	Ext.each(elb.ListenerDescriptions, function(item){
		item.Listener.PolicyNames = item.PolicyNames.member;
		listenerStore.add(item.Listener);		
		if(item.Listener.Protocol == 'HTTP' || item.Listener.Protocol == 'HTTPS')
			policyFlag = false;
	});
	
	var panel = Ext.create('Ext.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		title: 'Details',
		items: [{ 
			xtype: 'fieldset',
			title: 'General',
			defaults: {
				labelWidth: 150,
				xtype: 'displayfield',
			},
			items: [{
				fieldLabel: 'Name',
				value: elb['LoadBalancerName']
			},{
				fieldLabel: 'DNS name',
				value: elb['DNSName']
			},{
				fieldLabel: 'Created At',
				value: elb['CreatedTime']
			},{
				fieldLabel: 'Availability Zones',
				value: availableZones
			},{
				fieldLabel: 'Instances',
				value: instanceString
			}]
		},{ 
			xtype: 'fieldset',
			title: 'HealthCheck settings',
			defaults: {
				labelWidth: 150,
				xtype: 'displayfield',
			},
			items: [{
				fieldLabel: 'Interval',
				value: healthCheck['Interval']
			},{
				fieldLabel: 'Target',
				value: healthCheck['Target']
			},{
				fieldLabel: 'Healthy Threshold',
				value: healthCheck['HealthyThreshold']
			},{
				fieldLabel: 'Timeout',
				value: healthCheck['Timeout'] + ' seconds'
			},{
				fieldLabel: 'UnHealthy Threshold',
				value: healthCheck['UnhealthyThreshold']
			}]
		},{ 
			xtype: 'panel',
			border: false,
			height: 400,
			itemId: 'panel',
			bodyCls: 'scalr-ui-frame',
			layout: {
				type: 'hbox',
				align: 'stretch'
			},
			items: [{
				xtype: 'gridpanel',
				store: listenerStore,
				plugins: {
					ptype: 'gridstore'
				},
				viewConfig: {
					deferEmptyText: false,
					emptyText: "No Listeners found"
				},
				title: 'Listeners',
				itemId: 'listenerGrid',
				flex: 1,
				columns: [{
					text: 'Protocol',
					dataIndex: 'Protocol'
				},{
					flex: 1,
					text: 'LoadBalancer Port',
					sortable: false,
					dataIndex: 'LoadBalancerPort'
				},{
					flex: 1,
					text: 'Instance Port',
					sortable: false,
					dataIndex: 'InstancePort'
				},{
					text: 'Stickiness Policy',
					sortable: false,
					dataIndex: 'PolicyNames'
				},{
					xtype: 'optionscolumn',
					optionsMenu: [{ 
						itemId: "option.edit", iconCls: 'scalr-menu-icon-edit', text:'Settings',
						request: {
							confirmBox: {
								title: 'Associate Listener with Stickiness Policy',
								form:[{
									xtype: 'combo',
									name: 'policyName',
									queryMode: 'local',
									store: comboStore,
									editable: false,
									allowBlank: false,
									valueField: 'PolicyName',
									displayField: 'description',
									listeners:{
										beforerender: function(component, options){
											component.setValue(panel.down('#listenerGrid').getSelectionModel().getLastSelected().get('PolicyNames'),true);
										}
									}
								}]
							},
							processBox: {
								type: 'action'
							},
							scope: this,
							dataHandler: function (record) {
								this.currentRecord = record;
								var data = {
									cloudLocation: loadParams['cloudLocation'],
									elbPort: record.get('LoadBalancerPort')
								};
								this.url = '/tools/aws/ec2/elb/'+ elb['LoadBalancerName'] +'/xAssociateSp/';
								return data;
							},
							success: function (data, response, options) {
								var rowIndex = listenerStore.find('LoadBalancerPort', options.currentRecord.get('LoadBalancerPort'));
								listenerStore.getAt(rowIndex).set('PolicyNames', options.params.policyName || '');
							}
						}
					},{ 
						itemId: "option.delete", iconCls: 'scalr-menu-icon-delete', text:'Delete',
						request: {
							confirmBox: {
								msg: 'Remove Listener?',
								type: 'delete'
							},
							processBox: {
								type: 'delete',
								msg: 'Removing Listener. Please wait...'
							},
							dataHandler: function (record) {
								this.currentRecord = record;
								var data = {
									lbPort: record.get('LoadBalancerPort'),
									cloudLocation: loadParams['cloudLocation']
								}; 
								this.url = '/tools/aws/ec2/elb/'+ elb['LoadBalancerName'] +'/xDeleteListeners/';
								return data;
							},
							success: function (data, response, options) {
								listenerStore.remove(options.currentRecord);
								if (! policyFlag) {
									var flag = true;
									for(i = 0; i < listenerStore.data.length; i++){
										if(listenerStore.getAt(i).get('Protocol') == "HTTP" || listenerStore.getAt(i).get('Protocol') == "HTTPS") {
											flag = false;
											break;
										}
									}
									if (flag) {
										policyFlag = true;
										panel.down('#policyGrid').hide().disable();
									}
								}
							}
						}
					}],
					getOptionVisibility: function (item, record) {
						if (item.itemId == 'option.delete')
							return true;
						if (item.itemId == 'option.edit') {
							if(record.get('Protocol') == 'TCP' || record.get('Protocol') == 'SSL'){
								return false;
							}
							else return true;
						}
					}
				}],
				dockedItems: [{
					xtype: 'toolbar',
					dock: 'top',
					layout: {
						type: 'hbox',
						pack: 'start'
					},
					items: [{
						icon: '/ui/images/icons/add_icon_16x16.png',
						cls: 'x-btn-icon',
						tooltip: 'Add new listener',
						handler: function() {
							Scalr.Request({
								confirmBox: {
									title: 'Add new Listener',
									form: [{
										xtype: 'hiddenfield',
        								name: 'cloudLocation',
        								value: loadParams['cloudLocation']
									},{
        								xtype: 'hiddenfield',
        								name: 'elbName',
        								value: elb['LoadBalancerName']
    								},{
										xtype: 'combo',
										itemId: 'test',
										name: 'protocol',
										fieldLabel: 'Protocol',
										labelWidth: 120,
										editable: false,
										store: [ 'TCP', 'HTTP', 'SSL', 'HTTPS' ],
										queryMode: 'local',
										allowBlank: false,
										listeners: {
											change: function (field, value) {
												if (value == 'SSL' || value == 'HTTPS')
													this.next('[name="certificateId"]').show().enable();
												else
													this.next('[name="certificateId"]').hide().disable();
											}
										}
									},{
										xtype: 'textfield',
										name: 'lbPort',
										fieldLabel: 'Load balancer port',
										labelWidth: 120,
										allowBlank: false,
										validator: function (value) {
											if (value < 1024 || value > 65535) {
												if (value != 80 && value != 443)
													return 'Valid LoadBalancer ports are - 80, 443 and 1024 through 65535';
											}
											return true;
										}
									},{
										xtype: 'textfield',
										name: 'instancePort',
										fieldLabel: 'Instance port',
										labelWidth: 120,
										allowBlank: false,
										validator: function (value) {
											if (value < 1 || value > 65535)
												return 'Valid instance ports are one (1) through 65535';
											else
												return true;
										}
									},{
										xtype: 'combo',
										name: 'certificateId',
										fieldLabel: 'SSL Certificate',
										labelWidth: 120,
										hidden: true,
										disabled: true,
										editable: false,
										allowBlank: false,
										store: {
											fields: [ 'name','path','arn','id','upload_date' ],
											proxy: {
												type: 'ajax',
												reader: {
													type: 'json',
													root: 'data'
												},
												url: '/tools/aws/iam/servercertificates/xListCertificates/'
											}
										},
										valueField: 'arn',
										displayField: 'name'
									}],
									ok: 'Add'
								},
								processBox: {
									msg: 'Adding new Listener... Please wait, it can take a few minutes.',
									type: 'save'
								},
								url: '/tools/aws/ec2/elb/'+ elb['LoadBalancerName'] +'/xCreateListeners/',
								scope: this,
								success: function (data, response, options){
									listenerStore.add({Protocol: options.params.protocol,LoadBalancerPort: options.params.lbPort,InstancePort: options.params.instancePort});
									if(policyFlag){
										if(options.params.protocol == "HTTP" || options.params.protocol == "HTTPS"){
											policyFlag = false;
											this.up('#panel').down('#policyGrid').show().enable();
										}
									}
								}
							});
						}
					}]
				}]
			},{
				xtype: 'gridpanel',
				itemId: 'policyGrid',
				plugins: {
					ptype: 'gridstore'
				},
				viewConfig: {
					deferEmptyText: false,
					emptyText: 'No Stickiness Policies found'
				},
				margin: {
					left: 3
				},
				title: 'Stickiness Policies',
				flex: 1,
				columns: [{
					text: 'Type',
					dataIndex: 'PolicyType'
				},{
					flex: 2,
					text: 'Name',
					dataIndex: 'PolicyName'
				},{
					flex: 2,
					text: 'Cookie name / Exp. period',
					sortable: false,
					dataIndex: 'CookieSettings'
				},{
					xtype: 'optionscolumn',
					optionsMenu: [{
						itemId: "option.delete", iconCls: 'scalr-menu-icon-delete', text:'Delete',
						request: {
							confirmBox: {
								msg: 'Remove Stickiness Policy?',
								type: 'delete'
							},
							processBox: {
								type: 'delete',
								msg: 'Removing Stickiness Policy. Please wait...'
							},
							dataHandler: function (record) {
								this.currentRecord = record;
								var data = {
									policyName: record.get('PolicyName'),
									cloudLocation: loadParams['cloudLocation'],
									elbName: elb['LoadBalancerName']
								}; 
								this.url = '/tools/aws/ec2/elb/'+ elb['LoadBalancerName'] +'/xDeleteSp/';
								return data;
							},
							success: function (data, response, options ) {
								policyStore.remove(options.currentRecord);
								comboStore.remove(comboStore.getAt(comboStore.find('PolicyName', options.currentRecord.get('PolicyName'))));
							}
						} 
					}]
				}],
				disabled: policyFlag,
				store: policyStore,
				dockedItems: [{
					xtype: 'toolbar',
					dock: 'top',
					layout: {
						type: 'hbox',
						pack: 'start'
					},
					items: [{
						icon: '/ui/images/icons/add_icon_16x16.png',
						cls: 'x-btn-icon',
						tooltip: 'Add new cookie',
						handler: function() {
							Scalr.Request({
								confirmBox: {
									title: 'Create Stickiness Policies',
									form: [{
										xtype: 'hiddenfield',
        								name: 'cloudLocation',
        								value: loadParams['cloudLocation']
									},{
        								xtype: 'hiddenfield',
        								name: 'elbName',
        								value: elb['LoadBalancerName']
    								},{
										xtype: 'combo',	
										itemId: 'polis',
										name: 'policyType',
										editable: false,
										fieldLabel: 'Cookie Type',
										queryMode: 'local',
										store: [ ['AppCookie','App cookie'], ['LbCookie','Lb cookie'] ],
										value: 'AppCookie',
										listeners: {
											change: function (field, value){
												if(value == "LbCookie"){
													this.next('container').down('[name="cookieSettings"]').labelEl.update("Exp. period:");
													this.next('container').down('[name="Sec"]').show();
												}
												else{
													this.next('container').down('[name="cookieSettings"]').labelEl.update("Cookie Name:");
													this.next('container').down('[name="Sec"]').hide();
												}
											}
										}
									},{
										xtype: 'textfield',
										name: 'policyName',
										fieldLabel: 'Name',
										allowBlank: false
									},{
										xtype: 'container',
										layout: {
											type: 'hbox'
										},
										items:[{
											xtype: 'textfield',
											name: 'cookieSettings',
											fieldLabel: 'Cookie Name',
											allowBlank: false,
											labelWidth: 100,
											width: 365
										},{
											margin: {
												left: 2
											},
											xtype: 'displayfield',
											name: 'Sec',
											value: 'sec',
											hidden: true
										}]
									}],
									formValidate: true
								},
								scope: this,
								processBox: {
									type: 'save'
								},
								url: '/tools/aws/ec2/elb/'+ elb['LoadBalancerName'] +'/xCreateSp/',
								success: function (data, response, options) {
									policyStore.add({PolicyType: options.params.policyType,PolicyName: options.params.policyName,CookieSettings: options.params.cookieSettings});
									comboStore.add({PolicyName: options.params.policyName, description: options.params.policyName});
								}
							});
						}
					}]
				}]
			}] 
		}]
	});
	return panel;
});