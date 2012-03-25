Scalr.regPage('Scalr.ui.statistics.serversusage', function (loadParams, moduleParams) {
	var pricing = ['us-east-1','us-west-1', 'us-west-2', 'eu-west-1', 'ap-southeast-1', 'ap-northeast-1', 'sa-east-1'];
	Ext.each(pricing, function(item){
		pricing[item] = ['m1.small', 'm1.large', 'm1.xlarge', 't1.micro', 'm2.xlarge', 'm2.2xlarge', 'm2.4xlarge', 'c1.medium', 'c1.xlarge', 'cc1.4xlarge', 'cc2.8xlarge', 'cg1.4xlarge'];
	});
	Ext.each(pricing, function(item) {
		if (item == 'us-west-1' || item == 'us-east-1') {
			pricing[item]['m1.small'] = 0.085;
			pricing[item]['m1.large'] = 0.34;
			pricing[item]['m1.xlarge'] = 0.68;
			pricing[item]['t1.micro'] = 0.02;
			pricing[item]['m2.xlarge'] = 0.050;
			pricing[item]['m2.2xlarge'] = 1.00;
			pricing[item]['m2.4xlarge'] = 2.00;
			pricing[item]['c1.medium'] = 0.17;
			pricing[item]['c1.xlarge'] = 0.68;
			pricing[item]['cc1.4xlarge'] = 0;
			pricing[item]['cc2.8xlarge'] = 0;
			pricing[item]['cg1.4xlarge'] = 0;
			if(item == 'us-east-1') {
				pricing[item]['cc1.4xlarge'] = 1.30;
				pricing[item]['cc2.8xlarge'] = 2.40;
				pricing[item]['cg1.4xlarge'] = 2.10;
			}
				
		}
		if (item == 'us-west-2' || item == 'eu-west-1' || item == 'ap-southeast-1') {
			pricing[item]['m1.small'] = 0.095;
			pricing[item]['m1.large'] = 0.38;
			pricing[item]['m1.xlarge'] = 0.76;
			pricing[item]['t1.micro'] = 0.025;
			pricing[item]['m2.xlarge'] = 0.057;
			pricing[item]['m2.2xlarge'] = 1.14;
			pricing[item]['m2.4xlarge'] = 2.28;
			pricing[item]['c1.medium'] = 0.19;
			pricing[item]['c1.xlarge'] = 0.76;
			pricing[item]['cc1.4xlarge'] = 0;
			pricing[item]['cc2.8xlarge'] = 0;
			pricing[item]['cg1.4xlarge'] = 0;
		}

		if (item == 'ap-northeast-1') {
			pricing[item]['m1.small'] = 0.10;
			pricing[item]['m1.large'] = 0.40;
			pricing[item]['m1.xlarge'] = 0.80;
			pricing[item]['t1.micro'] = 0.027;
			pricing[item]['m2.xlarge'] = 0.060;
			pricing[item]['m2.2xlarge'] = 1.20;
			pricing[item]['m2.4xlarge'] = 2.39;
			pricing[item]['c1.medium'] = 0.20;
			pricing[item]['c1.xlarge'] = 0.80;
			pricing[item]['cc1.4xlarge'] = 0;
			pricing[item]['cc2.8xlarge'] = 0;
			pricing[item]['cg1.4xlarge'] = 0;
		}
		if (item == 'sa-east-1') {
			pricing[item]['m1.small'] = 0.115;
			pricing[item]['m1.large'] = 0.46;
			pricing[item]['m1.xlarge'] = 0.92;
			pricing[item]['t1.micro'] = 0.027;
			pricing[item]['m2.xlarge'] = 0.068;
			pricing[item]['m2.2xlarge'] = 1.36;
			pricing[item]['m2.4xlarge'] = 2.72;
			pricing[item]['c1.medium'] = 0.23;
			pricing[item]['c1.xlarge'] = 0.92;
			pricing[item]['cc1.4xlarge'] = 0;
			pricing[item]['cc2.8xlarge'] = 0;
			pricing[item]['cg1.4xlarge'] = 0;
		}
	});
	var totalSpent = function (records, operations, success){
		panel.down('#totalSpent').removeAll();
		panel.down('#totalSpent').add({
			value: '&nbsp;',
			flex: 1
		});
		var total = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
		for (i = 0; i<records.length; i++) {
			Ext.each(total, function(month){
				if (pricing[records[i].get('cloudLocation')] && pricing[records[i].get('cloudLocation')][records[i].get('instanceType')] && records[i].get('usage')[month]) {
					if (total[month] != undefined)
						total[month] += (pricing[records[i].get('cloudLocation')][records[i].get('instanceType')]*records[i].get('usage')[month]);
					else 
						total[month] = (pricing[records[i].get('cloudLocation')][records[i].get('instanceType')]*records[i].get('usage')[month]);
				}
			});
		}
		if(records.length) {
			panel.down('#totalSpent').removeAll();
			panel.down('#totalSpent').add({
				value: 'Total spent:',
				width: 280
			});
			Ext.each(total, function(month)	{
				if(total[month])
					panel.down('#totalSpent').add({
						value: '<div style= "width: 120px;"><center>$' + Ext.util.Format.round(total[month], 2) + '</center></div>'
					});
				else 
					panel.down('#totalSpent').add({
						value: '<div style= "width: 120px;"><center>$0</center></div>'
					});
			});
		}
	}
	var today = new Date();
	var store = Ext.create('store.store', {
		fields: [ 'cloudLocation', 'instanceType', 'usage'],
		proxy: {
			type: 'scalr.paging',
			extraParams: {year: today.getFullYear(), envId: Scalr.InitParams.user.envId, farmId: loadParams.farmId ? loadParams.farmId : 0},
			url: '/statistics/xListServersUsage'
		}
	});
	var farmStore = Ext.create('store.store', {
		fields: ['id', 'name'],
		proxy: {
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'data'
			},
			url: '/statistics/xListFarms',
			extraParams: {envId: Scalr.InitParams.user.envId}
		}
	});
	var panel = new Ext.create('Ext.grid.Panel', {
		title: 'Servers Usage Statistics (instance / hours)',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			Ext.applyIf(loadParams, { farmId: this.down('#farmId').getValue() });
			Ext.apply(this.store.proxy.extraParams, loadParams);
			
			this.down('#farmId').setValue(this.store.proxy.extraParams['farmId']);
		},
		store: store,
		stateId: 'grid-statistics-serversusage-view',
		plugins: {
			ptype: 'gridstore'
		},
		viewConfig: {
			emptyText: 'No statistics found'
		},
		columns: [
			{ xtype: 'templatecolumn', text: "Cloud Location / Instance Type", flex: 2, dataIndex: 'cloudLocation', sortable: true, tpl: new Ext.XTemplate('<tpl>{cloudLocation} / {instanceType} ({[this.price(values.cloudLocation, values.instanceType)]})</tpl>',
				{ 
					price :  function (location, insType) {
                        if(pricing[location] && pricing[location][insType])
						    return '$' + pricing[location][insType] + ' / hour';
                        else
                            return 'unknown';
					}
				})
			},
			{ xtype: 'templatecolumn', text: "January", width: 120, dataIndex: 'Jan', sortable: false,
			 tpl: '<tpl if="usage.Jan"><center>{usage.Jan}</center></tpl><tpl if="!usage.Jan"><center><img src="/ui/images/icons/false.png" /></center></tpl>'},
			{ xtype: 'templatecolumn', text: "February", width: 120, dataIndex: 'Feb', sortable: false,
			 tpl: '<tpl if="usage.Feb"><center>{usage.Feb}</center></tpl><tpl if="!usage.Feb"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "March", width: 120, dataIndex: 'Mar', sortable: false,
			 tpl: '<tpl if="usage.Mar"><center>{usage.Mar}</center></tpl><tpl if="!usage.Mar"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "April", width: 120, dataIndex: 'Apr', sortable: false,
			 tpl: '<tpl if="usage.Apr"><center>{usage.Apr}</center></tpl><tpl if="!usage.Apr"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "May", width: 120, dataIndex: 'May', sortable: false,
			 tpl: '<tpl if="usage.May"><center>{usage.May}</center></tpl><tpl if="!usage.May"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "June", width: 120, dataIndex: 'Jun', sortable: false,
			 tpl: '<tpl if="usage.Jun"><center>{usage.Jun}</center></tpl><tpl if="!usage.Jun"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "July", width: 120, dataIndex: 'Jul', sortable: false,
			 tpl: '<tpl if="usage.Jul"><center>{usage.Jul}</center></tpl><tpl if="!usage.Jul"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "August", width: 120, dataIndex: 'Aug', sortable: false,
			 tpl: '<tpl if="usage.Aug"><center>{usage.Aug}</center></tpl><tpl if="!usage.Aug"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "September", width: 120, dataIndex: 'Sep', sortable: false,
			 tpl: '<tpl if="usage.Sep"><center>{usage.Sep}</center></tpl><tpl if="!usage.Sep"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "October", width: 120, dataIndex: 'Oct', sortable: false,
			 tpl: '<tpl if="usage.Oct"><center>{usage.Oct}</center></tpl><tpl if="!usage.Oct"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "November", width: 120, dataIndex: 'Nov', sortable: false,
			 tpl: '<tpl if="usage.Nov"><center>{usage.Nov}</center></tpl><tpl if="!usage.Nov"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "December", width: 120, dataIndex: 'Dec', sortable: false,
			 tpl: '<tpl if="usage.Dec"><center>{usage.Dec}</center></tpl><tpl if="!usage.Dec"><center><img src="/ui/images/icons/false.png" /></center></tpl>' }
		],
		dockedItems: [{
			xtype: 'toolbar',
			height: 27,
			dock: 'top',
			items: [{
				xtype: 'combo',
				fieldLabel: 'Year',
				labelWidth: 30,
				store: moduleParams.years,
				valueField: 'name',
				displayField: 'name',
				editable: false,
				value: today.getFullYear().toString(),
				queryMode: 'local',
				itemId: 'years',
				iconCls: 'no-icon',
				listeners: {
					change: function(field, value) {
						store.proxy.extraParams.year = value;
						store.load(totalSpent);
					}
				}
			}, '-', {
				xtype: Scalr.InitParams.user.type == 'AccountOwner' ? 'combo' : 'displayfield',
				fieldLabel: 'Environment',
				labelWidth: 70,
				store: {
					fields: ['id', 'name'],
					data: moduleParams.env,
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				editable: false,
				value: Scalr.InitParams.user.type == 'AccountOwner' ? Scalr.InitParams.user.envId : moduleParams.env[Scalr.InitParams.user.envId],
				queryMode: 'local',
				itemId: 'envId',
				iconCls: 'no-icon',
				listeners: {
					change: function(field, value) {
						store.proxy.extraParams.envId = farmStore.proxy.extraParams.envId = value;
						var farmId = field.up().down('#farmId').getValue();
						field.up().down('#farmId').setValue('0');
						farmStore.load();
						if(farmId == '0')
							store.load(totalSpent);
					}
				}
			},'-', {
				xtype: 'combo',
				fieldLabel: 'Farm',
				labelWidth: 40,
				store: farmStore,
				valueField: 'id',
				displayField: 'name',
				editable: false,
				value: loadParams['farmId'] || '0',
				queryMode: 'local',
				itemId: 'farmId',
				iconCls: 'no-icon',
				listeners: {
					change: function(field, value) {
						store.proxy.extraParams.farmId = value;
						store.load(totalSpent);
					}
				}
			},'->', {
				text: 'Download Statistic',
				iconCls: 'scalr-ui-btn-icon-download',
				handler: function () {
					var params = Scalr.utils.CloneObject(store.proxy.extraParams);
					params['action'] = 'download';
					Scalr.utils.UserLoadFile('/statistics/xListServersUsage?' + Ext.urlEncode(params));
				}
			}]
		},{
			xtype: 'toolbar',
			dock: 'bottom',
			itemId: 'totalSpent',
			defaults: {
				xtype: 'displayfield',
				width: 120,
				height: 20,
				hideLabel: true,
				layout: {
					type: 'hbox',
					pack: 'middle'
				}
			},
			items: [{
				value: '&nbsp;',
				flex: 1
			}]
		}],
		tools: [{
			type: 'refresh',
			handler: function () {
				Scalr.event.fireEvent('refresh');
			}
		}]
	});
	farmStore.load();
	store.load(totalSpent);
	return panel;
});