Scalr.regPage('Scalr.ui.account.teams.permissiongroups', function (loadParams, moduleParams) {
	
	var createCont = function(name, props, group) {
		var ar = [];
		
		var title = name.replace(/_/g, '/'), replaceTitles = {
			'Bundletasks': 'Bundle tasks',
			'Dbmsr': 'Database manager',
			'Dm': 'Deployments manager',
			'Dnszones': 'DNS Zones',
			'Schedulertasks': 'Task scheduler'
			
		};

		Ext.each(props, function(p) {
			var c = Ext.isDefined(group[name]) ? ((group[name].join(',').match(p) || group[name].join(',').match('FULL')) ? true : false) : false
			ar.push({
				boxLabel: p,
				name: 'permission[' + name + '][' + p + ']',
				checked: c
			});
		});
		
		return {
			xtype: 'fieldset',
			checkboxToggle: true,
			checkboxName: 'controller[' + name + ']',
			collapsed: Ext.isDefined(group[name]) ? false : true,
			title: replaceTitles[title] || title,
			items: [{
				xtype: 'displayfield',
				value: 'Full access',
				hidden: ar.length != 0 ? true : false 
			}, {
				xtype: 'combo',
				store: [ ['FULL', 'Full access'], ['VIEW', 'View only with ...']],
				editable: false,
				value: Ext.isDefined(group[name]) ? (group[name].join(',').match('FULL') ? 'FULL' : 'VIEW') : (ar.length == 0 ? 'FULL' : 'VIEW'),
				queryMode: 'local',
				hidden: ar.length == 0 ? true : false,
				width: 200,
				name: 'access[' + name + ']',
				listeners: {
					change: function() {
						if (this.getValue() == 'FULL') {
							this.next().disable();
							this.next().items.each(function (item) {
								item.setValue(true);
							});
						} else {
							this.next().enable();
						}
					}
				}
			}, {
				xtype: 'checkboxgroup',
				columns: 3,
				items: ar,
				disabled: Ext.isDefined(group[name]) ? (group[name].join(',').match('FULL') ? true : false) : false
			}]
		};
	};
	
	var panel = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		title: 'Account &raquo; Teams &raquo; ' + moduleParams['teamName'] + ' &raquo; Permission Group &raquo; ' + moduleParams['groupName'],
		scalrOptions: {
			maximize: 'all',
			modal: true,
		},
		layout: {
			type: 'hbox',
			align: 'stretch',
			pack: 'start'
		},
		items: [{
			xtype: 'panel',
			flex: 1,
			itemId: 'column0',
			bodyCls: 'scalr-ui-frame',
			border: false
		}, {
			xtype: 'panel',
			flex: 1,
			itemId: 'column1',
			margin: {
				left: 3
			},
			bodyCls: 'scalr-ui-frame',
			border: false
		}, {
			xtype: 'panel',
			flex: 1,
			itemId: 'column2',
			margin: {
				left: 3
			},
			bodyCls: 'scalr-ui-frame',
			border: false
		}],
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
				text: 'Save',
				handler: function () {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						url: '/account/teams/' + moduleParams['teamId'] + '/xSavePermissionGroup',
						params: {
							groupId: moduleParams['groupId']
						},
						form: panel.getForm(),
						success: function () {
							Scalr.event.fireEvent('close');
						}
					});
				},
				width: 80
			}, {
				xtype: 'button',
				text: 'Cancel',
				margin: {
					left: 5
				},
				width: 80,
				handler: function () {
					Scalr.event.fireEvent('close');
				}
			}]
		}]
	});
	
	var cnt = 0;
	for(var i in moduleParams['permissions']) {
		panel.down('#column' + (cnt % 3)).add(createCont(i, moduleParams['permissions'][i], moduleParams['group']));
		cnt += 1;
	}
	
	return panel;
});
