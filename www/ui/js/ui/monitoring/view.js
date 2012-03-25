Scalr.regPage('Scalr.ui.monitoring.view', function (loadParams, moduleParams) {
	function refreshStat(){
		Ext.each (panel.items.items, function (panelItem) {
			Ext.each(panelItem.items.items,function(windowItem) {
				fillStatistic(
					panelItem.farm, 
					windowItem.down('#viewMode').type, 
					windowItem.down('#viewMode').text, 
					panelItem.role
				);
			});
		});
	}
	function addDockMenu(farm, watchername, type, role) {
		if(panel.down('#' + watchername + farm + role)){
			panel.down('#' + watchername + farm + role).removeDocked(panel.down('#' + watchername + farm + role).dockedItems.getAt(1), true);
			panel.down('#' + watchername + farm + role).addDocked( {
				xtype: 'toolbar',
				dock: 'top',
				items: [{
					text: type,
					type: watchername,
					itemId: 'viewMode',
					menu: [{
						text: 'Daily',
						itemId: 'daily',
						group: 'viewMode' + farm + role + watchername,
						checked: type == 'daily'? true: '',
						listeners: {
							click: function(item, e, opt) {
								fillStatistic(farm, watchername, 'daily', role);
							}
						}
					},{
						text: 'Weekly',
						itemId: 'weekly',
						group: 'viewMode' + farm + role + watchername,
						checked: type == 'weekly'? true: '',
						listeners: {
							click: function(item, e, opt){
								fillStatistic(farm, watchername, 'weekly', role);
							}
						}
					},{
						text: 'Monthly',
						itemId: 'monthly',
						group: 'viewMode' + farm + role + watchername,
						checked: type == 'monthly'? true: '',
						listeners: {
							click: function(item, e, opt) {
								fillStatistic(farm, watchername, 'monthly', role);
							}
						}
					},{
						text: 'Yearly',
						itemId: 'yearly',
						group: 'viewMode' + farm + role + watchername,
						checked: type == 'yearly'? true: '',
						listeners: {
							click: function(item, e, opt){
								fillStatistic(farm, watchername, 'yearly', role);
							}
						}
					}]
				}]
			});
		} 
	}
	function fillStatistic(farm, watchername, type, role) {
		if(panel.down('#' + watchername + farm + role)){
			if(panel.down('#' + watchername + farm + role).body) 
				panel.down('#' + watchername + farm + role).body.update('<div style="position: relative; top: 48%; text-align: center; vertical-align: top; width: 100%; height: 50%;"><img src = "/ui/images/icons/anim/loading_16x16.gif">&nbsp;Loading...</div>');
			panel.down('#' + watchername + farm + role).html = '<div style="position: relative; top: 48%; text-align: center; width: 100%; vertical-align: top; height: 50%;"><img src = "/ui/images/icons/anim/loading_16x16.gif">&nbsp;Loading...</div>';
			Scalr.Request({
				scope: this,
				url: '/server/statistics.php?version=2&task=get_stats_image_url&farmid=' + farm + '&watchername=' + watchername + '&graph_type=' + type + '&role=' + role,
				success: function (data, response, options) {
					if(panel.down('#' + watchername + farm + role)){
						addDockMenu(farm, watchername, type, role);
						panel.down('#' + watchername + farm + role).body.update('<div style="position: relative; text-align: center; width: 100%; height: 50%;"><img src = "' + data.msg + '"/></div>');
					}
				},
				failure: function(data, response, options) {
					if(panel.down('#' + watchername + farm + role)){
						addDockMenu(farm, watchername, type, role);
						panel.down('#' + watchername + farm + role).body.update('<div style="position: relative; top: 48%; text-align: center; width: 100%; height: 50%;"><font color = "red">' + data.msg + '</font></div>');
					}
				}
			});
		}
	}
	function newStatistic(record) {
		var role;
		var farm;
		var panelTitle = '';
		if(record.get('parentId') == 'root') {
			role = 'FARM';
			farm = record.raw.itemId;
			panelTitle = record.get('value');
		}
		else {
			if(record.parentNode.get('parentId') == 'root') {
				farm = record.parentNode.get('itemId');
				panelTitle += record.parentNode.get('value') + '&nbsp;&rarr;&nbsp;';
			}
			else {
				farm = record.parentNode.parentNode.get('itemId');
				panelTitle += record.parentNode.parentNode.get('value') + '&nbsp;&rarr;&nbsp;' + record.parentNode.get('value');
			}
			role = record.raw.itemId;
			panelTitle += record.get('value');
		}
		panel.add({
			xtype: 'panel',
			farm: farm,
			role: role,
			border: false,
			itemId: record.get('text') + record.get('id'),
			layout: panel.down('#compareMode').checked ?
				{ type: 'anchor'} :
				{ type: 'table', columns: 2,
				tdAttrs: {
            		style: {'vertical-align': 'top'}
       			}
        	},
			defaults: {
				width: 548,
				bodyCls: 'scalr-ui-frame',
				margin: 5,
				bodyPadding: 5,
				xtype: 'panel',
				html: '<div style="position: relative; top: 48%; text-align: center; width: 100%; height: 50%;"><img src = "/ui/images/icons/anim/loading_16x16.gif">&nbsp;Loading...</div>'
			},
			items:[{
				height: 402,
				title: panelTitle + ' / Memory Usage',
				itemId: 'MEMSNMP' + farm + role,
				listeners: {
					afterrender: function(){
						fillStatistic(farm, 'MEMSNMP', 'daily', role);
					}
				}
			},{
				height: 354,
				title: panelTitle + ' / CPU Utilization',
				itemId: 'CPUSNMP' + farm + role,
				listeners: {
					afterrender: function(){
						fillStatistic(farm, 'CPUSNMP', 'daily', role);
					}
				}
			},{
				height: 320,
				title: panelTitle + ' / Load averages',
				itemId: 'LASNMP' + farm + role,
				listeners: {
					afterrender: function(){
						fillStatistic(farm, 'LASNMP', 'daily', role);
					}
				}
			},{
				height: 266,
				title: panelTitle + ' / Network Usage',
				itemId: 'NETSNMP' + farm + role,
				listeners: {
					afterrender: function(){
						fillStatistic(farm, 'NETSNMP', 'daily', role);
					}
				}
			}]
		});
		
		panel.body.child('.x-box-inner').applyStyles({width: '100%', height: '100%', overflow: 'auto'});
	}
	panel = Ext.create('Ext.panel.Panel', {
		title: 'Farms &raquo; Monitoring',
		bodyCls: 'scalr-ui-frame',
		stateId: 'monitoring-view',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		layout: {
			type: 'hbox',
			align: 'top'
		},
		defaults: {
			bodyCls: 'scalr-ui-frame'
		},
		dockedItems: [{
			dock: 'left',
			xtype: 'treepanel',
			headerPosition: 'left',
			itemId: 'tree',
			width: 300,
			rootVisible: false,
			store: {
				fields: [ 'itemId', 'value', 'text' ],
				root: {
					text: 'Monitoring',
					expanded: true,
					children: moduleParams['children']
				}
			},
            dockedItems:[{
				xtype: 'toolbar',
				dock: 'top',
				layout: {
					type: 'hbox',
					pack: 'start'
				},
				items:[{
					xtype: 'button',
					text: 'Filter',
					itemId: 'filter',
					iconCls: 'scalr-ui-btn-icon-filter',
					menu: [{
						xtype: 'triggerfield',
						triggerCls: 'x-form-search-trigger',
						emptyText: 'Filter',
						onTriggerClick: function() {
							var trigger = this;
							if(trigger.getRawValue() == '') {
								Ext.each (panel.down('#tree').getRootNode().childNodes, function(farmItem) {
									farmItem.cascadeBy(function(){
										el = Ext.get(panel.down('#tree').getView().getNodeByRecord(this));
										el.setVisibilityMode(Ext.Element.DISPLAY);
	        							el.setVisible(true);
									});
								});
							}
							else {
								Ext.each (panel.down('#tree').getRootNode().childNodes, function(farmItem) {
									farmItem.cascadeBy(function(){
										el = Ext.get(panel.down('#tree').getView().getNodeByRecord(this));
										el.setVisibilityMode(Ext.Element.DISPLAY);
										if(this.get('text').search(trigger.getRawValue()) == -1)
	        								el.setVisible(false);
	        							else 
	        								el.setVisible(true);
									});
								});
							}
    					}
					}]
				},'-',{
					xtype: 'checkbox',
					itemId: 'compareMode',
					boxLabel: 'Compare Mode',
					listeners: {
						change: function(field, newValue, oldValue, opt) {
							if (newValue) {
								node = panel.down('#tree').getChecked();
    							if(node.length) {
    								panel.remove((node[0].get('text') + node[0].get('id')));
									newStatistic(node[0]);
    							}
							}
							else{
								panel.removeAll(true);
								arr = panel.down('#tree').getChecked();
								node = arr[0];
								if(panel.down('#tree').getSelectionModel().getLastSelected().get('checked')) node = panel.down('#tree').getSelectionModel().getLastSelected();
								for(i =0; i < arr.length; i++) {
									arr[i].set('checked', false);
								}
								node.set('checked', true);
								newStatistic(node);
							}
						}
					}
				},'-',{
					xtype: 'checkbox', 
					boxLabel: 'Auto Refresh',
					listeners: {
						change: function(field, newValue, oldValue, opt) {
							if(newValue) {
								taskManager = {
									run: refreshStat,
    								interval: 60000
								}
								Ext.TaskManager.start(taskManager);
							}
							else Ext.TaskManager.stop(taskManager);
						}
					}
				}]
			}],
            listeners: {
    			itemclick: function( view, record, item, index, e, options ) {
    				if(!panel.down('#compareMode').checked){
    					panel.removeAll(true);
    					newStatistic(record);
    					node = panel.down('#tree').getChecked();
    					if(node.length)
							node[0].set('checked', false);
						record.set('checked', true);
    				}
    			},
    			checkchange: function(node, check, opt) {
    				if(!panel.down('#compareMode').checked)
						node.set('checked', false);
					if(panel.down('#compareMode').checked && check) newStatistic(node);
					else if(panel.down('#compareMode').checked && !check){
						if(panel.down('#tree').getChecked().length!=0)
							panel.remove((node.get('text') + node.get('id')));
						if(panel.down('#tree').getChecked().length==0)
							node.set('checked', true);
					}
    			},
    			afterrender: function(component, opt) {
    				loadPath = '';
    				if( loadParams['server_index'] && loadParams['role'] && component.getRootNode().findChild('itemId', ('INSTANCE_' + loadParams['role'] + '_' + loadParams['server_index']), true) ) {
    					loadPath = 'INSTANCE_' + loadParams['role'] + '_' + loadParams['server_index'];
    				}
    				else {
    					if(loadParams['role'] && component.getRootNode().findChild('itemId', loadParams['role'], true) ) {
	    					loadPath = loadParams['role'];
    					}
    					else {
    						if(loadParams['farmId'] && component.getRootNode().findChild('itemId', loadParams['farmId'], true) ) {
		    					loadPath = loadParams['farmId'];
    						}
    					}
    				}
    				if(loadPath != '') {
    					component.selectPath(component.getRootNode().findChild('itemId', loadPath, true).getPath());
		    			component.getRootNode().findChild('itemId', loadPath, true).set('checked', true);
		    			newStatistic(component.getRootNode().findChild('itemId', loadPath, true));
    				}
    			}
            }
		}],
		listeners: {
			resize: function() {
				panel.body.child('.x-box-inner').applyStyles({width: '100%', height: '100%', overflow: 'auto'});
			}
		}
	});
	var taskManager;
	return panel;
});
