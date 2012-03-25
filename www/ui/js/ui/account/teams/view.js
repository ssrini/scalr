Scalr.regPage('Scalr.ui.account.teams.view', function (loadParams, moduleParams) {
	var menu = Ext.create('Ext.menu.Menu', {
	    hidden: true
	});

	var store = Ext.create('store.store', {
		fields: [
			'id', 'name', 'description', 'owner', 'ownerTeam', 'users', 'environments', 'groups'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/account/teams/xListTeams'
		},
		remoteSort: true
	});
	
	function setMenu(record, targetParams) {
		menu.removeAll();
		if (targetParams.name == '' && (moduleParams.teamManage || record.get('ownerTeam'))) {
			if (targetParams.value == 'users')
				menu.add({
					iconCls: 'scalr-menu-icon-edit',
			        text: 'Create new & add',
			        listeners:{
			        	click: function(){
			        		Scalr.event.fireEvent('redirect', '#/account/users/create');
			        	}
			        }
				},{
		    		xtype: 'menuseparator'
			    },{
			    	text: 'Add existed',
			    	listeners:{
			        	click: function(){
			        		var usersStore = Ext.create('store.store', {
				    			fields: ['id', 'email', 'fullname'],
								proxy: {
									type: 'ajax',
									reader: {
										type: 'json',
										root: 'data'
									},
									url: '/account/teams/xGetUsers',
									extraParams: {teamId: record.get('id')}
								},
								remoteSort: true
				    		});
				    		usersStore.load();
			        		Scalr.Request({
								confirmBox: {
									title: 'Add users',
									form: [{
										xtype: 'hidden',
										name: 'teamId',
										value: record.get('id')
									},{
										xtype: 'hidden',
										name: 'userPermissions',
										value: 'full'
									},{
										xtype: 'hidden',
										name: 'userGroups',
										value: []
									},{
										xtype: 'combo',
										name: 'userId',
										store: usersStore,
										displayField: 'email',
										valueField: 'id',
										queryMode: 'local'
									}],
									ok: 'Add',
									formValidate: true
								},
								processBox: {
									msg: 'Adding...',
									type: 'action'
								},
								scope: this,
								url: '/account/teams/xAddUser',
								success: function (data, response, options){
									var newUser = [];
									Ext.each(usersStore.getRange(), function(item){
										if(item.get('id') == options.params.userId)
											newUser = item.data;
									});
									newUser['groups'] = options.params.userGroups;
									newUser['permissions'] = options.params.userPermissions;
									var users = record.get('users');
									users.push(newUser);
									record.set('users', users);
								}
							});
			        	}
			        }
			    });
			if (targetParams.value == 'groups')
				menu.add({
			    	iconCls: 'scalr-menu-icon-edit',
			        text: 'Create new & add',
			        listeners:{
			        	click: function(){
		        			Scalr.Request({
								confirmBox: {
									title: 'Create New permissions group',
									form: [{
										xtype: 'textfield',
										fieldLabel: 'Name',
										name: 'name',
										allowBlank: false,
										validator: function (value) {
											var isExist = false;
											Ext.each(record.get('groups'), function(item){
												if(item.name == value)
													isExist = true;
											});
											if(isExist)
												return 'such name is already existed';
											return true;
										}
									}],
									ok: 'Add',
									formValidate: true,
									closeOnSuccess: true,
								},
								processBox: {
									type: 'save'
								},
								url: '/account/teams/' + record.get('id') + '/xCreatePermissionGroup/',
								success: function (data) {
									var groups = record.get('groups');
									groups.push(data.group);
									record.set('groups', groups);
								}
							});
			        	}
			        }
			   });
		}
		
		else {
			if (moduleParams.teamManage || record.get('ownerTeam')) {
				if(targetParams.name == 'Owner' || targetParams.name == 'User' || targetParams.name == 'Group')
				menu.add({
					iconCls: 'scalr-menu-icon-edit',
		       		text: targetParams.name == 'Group' ? 'Edit Group' : 'Edit User', // work it... for teamOwner
		        	href: (targetParams.name == 'User' || targetParams.name == 'Owner') ? '#/account/users/' + targetParams.value + '/edit' : '#/account/teams/' + record.get('id') + '/permissionGroup?groupId=' + targetParams.value
				});
			if(targetParams.name == 'User')
				menu.add({
					iconCls: 'scalr-menu-icon-edit',
					text: 'Edit Permissions',
					listeners: {
			        	click: function () {
		        			var groups = [];
			        		var userInfo = '';
			        		Ext.each (record.get('users'), function (user) {
								if(user.id == targetParams.value){
									userInfo = user;
									Ext.each (record.get('groups'), function (rec) {
										var isChecked = false;
										Ext.each(user.groups, function (group) {
											if(group.id == rec.id)
												isChecked = true;
										});
										groups.push({
											xtype: 'checkbox',
											boxLabel: rec.name,
											inputValue: rec.id,
											checked: isChecked,
											submitValue: false
										
										});
									});
								}
							});
			        		Scalr.Request({
								confirmBox: {
									title: 'Manage Permissions',
									form: [{
										xtype: 'hidden',
										name: 'teamId',
										value: record.get('id')
									},{
										xtype: 'hidden',
										name: 'userId',
										value: targetParams.value
									},{
										xtype: 'radiofield',
										name: 'userPermissions',
										inputValue: 'owner',
										checked: userInfo.permissions == 'owner' ? true : false,
										boxLabel: 'Owner',
										hidden: !moduleParams.teamManage
									},{
										xtype: 'radiofield',
										name: 'userPermissions',
										inputValue: 'full',
										checked: userInfo.permissions == 'full' ? true : false,
										boxLabel: 'Full'
									},{
										xtype: 'radiofield',
										name: 'userPermissions',
										inputValue: 'groups',
										boxLabel: 'Groups',
										checked: userInfo.permissions == 'groups' ? true : false,
										listeners: {
											change: function(field, newValue, oldValue, eOpts) {
												if(newValue)
													field.next('#groups').show();
												else
													field.next('#groups').hide();
											}
										}
									},{
										hidden: userInfo.permissions == 'groups' ? false : true,
										xtype: 'fieldset',
										itemId: 'groups',
										title: 'Select',
										items: [ Ext.isEmpty(record.get('groups') ) ? {
											xtype: 'displayfield',
											value: '<center>Create permissions group before</center>'
											} : {
											xtype: 'checkboxgroup',
								        	columns: 3,
											getSubmitData: function () {
												if(!this.up('#groups').hidden){
													var groups = [];
													this.items.each(function (item) {
														if (item.getValue())
															groups.push(item.inputValue);
													});
													
													return { 'userGroups': Ext.encode(groups) };
												}
											},
											vertical: true,
								        	items: groups
										}]
									}]
								},
								processBox: {
									msg: 'Updating...',
									type: 'action'
								},
								scope: this,
								url: '/account/teams/xAddUser',
								success: function (data, response, options){
									Ext.each (record.get('users'), function (user) {
										if(user.id == targetParams.value){
											user.permissions = options.params.userPermissions;
											var groups = [];
											if(user.permissions == 'groups')
												Ext.each (record.get('groups'), function (group) {
													Ext.each (Ext.decode(options.params.userGroups), function (newGroup) {
														if(group.id == newGroup)
															groups.push(group);
													});
												});
											user.groups = groups;
										}
									});
								}
							});

			        	}
			       	}
				});
			}
			
			if(targetParams.name == 'Owner' || targetParams.name == 'User')
				menu.add({
					iconCls: 'scalr-menu-icon-info',
		       		text: 'View info',
		        	href: '#/account/users/view?userId=' + targetParams.value
				});
			if (moduleParams.teamManage || record.get('ownerTeam')) {
				if(targetParams.name == 'User' || targetParams.name == 'Group')
				menu.add({
			    	xtype: 'menuseparator'
			    },{
			        text: 'Remove From Team',
			        iconCls: 'scalr-menu-icon-delete',
			        listeners: {
			        	click: function () {
			        		Scalr.Request({
								confirmBox: {
									msg: 'Remove ' + targetParams.name + '?',
									type: 'delete'
								},
								processBox: {
									msg: 'Removing...',
									type: 'delete'
								},
								scope: this,
								url: (targetParams.name == 'Group') ? '/account/teams/xRemovePermissionGroup' : '/account/teams/xRemoveUser',
								params: (targetParams.name == 'Group') ? {groupId: targetParams.value, teamId: record.get('id')} : {userId: targetParams.value, teamId: record.get('id')},
								success: function (data, response, options){
									if(targetParams.name == 'Group')
										arrayName = 'groups';
									else 
										arrayName = 'users';
									var objects = [];
									Ext.each(store.getAt(record.index).get(arrayName), function(item){
										if(targetParams.value != item.id)
											objects.push(item);
									});
									record.set(arrayName, objects);
								}
							});
			        	}
			        }
				});
			}
			
		}
		menu.setPosition(targetParams.left + 10, targetParams.top + 113);
		menu.show(); 
	}
	var imageTpl = new Ext.XTemplate(
    	'<tpl for=".">',
	        '<div class = "scalr-ui-account-teams-list" style="',
	        	'<tpl if="xindex%2==0">background-color: #F3F7FC;</tpl>',
	        	'">',
	        	'<div class = "block" style = "width: 40%;">',
	          		'<span class = "title">{name}&nbsp;<tpl if="this.teamManage||ownerTeam"><a href="#/account/teams/{id}/edit"><div class = "scalr-menu-icon-configure" style = "display: inline-block;"></div></a></tpl></span>',
	          		'<br/><span class="desc">&nbsp;{description}</span>',
	          		'<div>',
	          			'<div class = "scalr-menu-icon-environment" style = "display: inline-block;"></div>',
	          			'<tpl for="environments">',
		          			'<a<tpl if="this.teamManage"> href = "#/environments/{id}/edit"</tpl> class = "scalr-ui-account-teams-list-button">',
			          			'<div style = "background-color: #4483D0;">',
			          				'{name}',
			          			'</div>',
		          			'</a>',
		          		'</tpl>',
	          		'</div>',
	          	'</div>',
	          	'<div class="block" style = "width: 57%;">',
		          	'<div class = "scalr-ui-account-teams-list-groups">',
			          	'<div class="scalr-ui-account-teams-list-category" value = "users"> Users<tpl if="this.teamManage||ownerTeam"><div class="scalr-ui-account-teams-list-menu-arrow" value = "users"></div></tpl><tpl if="!ownerTeam"><tpl if="!this.teamManage"> :</tpl></tpl>',
			          	'</div>', 
			          	'<div style="padding-left: 135px; margin-top: -15px;" class="scalr-ui-account-teams-list-owner"><a class="scalr-ui-account-teams-list-owner-link" name="Owner" value={owner.id}>{owner.email}<div class="scalr-ui-account-teams-list-menu-arrow" name="Owner" value={owner.id}></div></a>',
			          	'<tpl for="users">',
			          		'<tpl if="permissions!=\'owner\'">',
			          			'<div class="scalr-ui-account-teams-list-users">',
			          				'<a class = "scalr-ui-account-teams-list-category-link" name="User" value={id}>{email}<div class="scalr-ui-account-teams-list-menu-arrow" name="User" value={id}></div></a>',
			          			'</div>',
			          		'</tpl>',
			          	'</tpl>',
			          	'</div>',
		          	'</div>',
		          	'<tpl if="this.permissionsManage">',
		          	'<div class = "scalr-ui-account-teams-list-groups">',
			          	'<div class="scalr-ui-account-teams-list-category" value = "groups"> Permission groups<tpl if="this.teamManage||ownerTeam"><div class="scalr-ui-account-teams-list-menu-arrow" value = "groups"></div></tpl><tpl if="!ownerTeam"><tpl if="!this.teamManage"> :</tpl></tpl>',
			          	'</div>',
			          	'<div style="padding-left: 135px; margin-top: -15px;"> ',
			          	 	'<tpl for="groups">',
			          	 		'<div class="scalr-ui-account-teams-list-permissions">',
			          				'<a class = "scalr-ui-account-teams-list-category-link" name="Group" value={id}>{name}<div class="scalr-ui-account-teams-list-menu-arrow" name="Group" value={id}></div></a>',
			          			'</div>',
			          		'</tpl>',
			          	'</div>',
		          	'</div>',
		          	'</tpl>',
	          	'</div>',
	        '</div>',
	    '</tpl>',
	    {
	    	teamManage: moduleParams['teamManage']
	    }, {
	    	permissionsManage: moduleParams['permissionsManage']
	    });

	
	var panel = Ext.create('Ext.panel.Panel', {
		title: 'Account &raquo; Teams &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = {};
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		layout: 'card',
		plugins: [{
			ptype: 'switchview'
		}],

		items: [{
			itemId: 'grid',
			xtype: 'grid',
			border: false,
			store: store,
			stateId: 'grid-account-teams-view',
			
			plugins: [{
				ptype: 'gridstore'
			}],

			viewConfig: {
				emptyText: 'No teams found'
			},

			columns: [
				{ text: 'ID', width: 70, dataIndex: 'id', sortable: true },
				{ text: 'Name', flex: 1, dataIndex: 'name', sortable: true },
				{ text: 'Owner', flex: 1, dataIndex: 'owner', sortable: false, xtype: 'templatecolumn', tpl:
					'<a href="#/account/users/view?userId={owner.id}">{owner.email}</a>'
				},
				{ text: 'Users', width: 90, dataIndex: 'users', sortable: false, xtype: 'templatecolumn', tpl:
					'{users.length} [<a href="#/account/users/view?teamId={id}">View</a>]'
				}, { text: 'Environments', flex: 1, dataIndex: 'environments', sortable: false, xtype: 'templatecolumn', tpl: new Ext.XTemplate(
					'<tpl if="this.teamManage">' +
						'<tpl for="environments">' +
							'<a href="#/environments/{id}/edit">{name}</a>' +
							'<tpl if="xindex < xcount">, </tpl>' +
						'</tpl>' +
					'</tpl>' +
					'<tpl if="!this.teamManage">' +
						'<tpl for="environments">' +
							'{name}' +
							'<tpl if="xindex < xcount">, </tpl>' +
						'</tpl>' +
					'</tpl>'
				, {
					teamManage: moduleParams['teamManage']
				})},
				{
					xtype: 'optionscolumn',
					getVisibility: function (record) {
						return moduleParams['teamManage'] || record.get('ownerTeam');
					},
					getOptionVisibility: function (item, record) {
						if (moduleParams['teamManage'])
							return true;
						
						if (item.itemId == 'edit' || item.itemId == 'permissionGroups') {
							if (record.get('ownerTeam'))
								return true;
							else
								return false;
						}
						
						return false;
					},
					optionsMenu: [{
						text: 'Edit',
						itemId: 'edit',
						iconCls: 'scalr-menu-icon-edit',
						href: '#/account/teams/{id}/edit'
					}, {
						text: 'Remove',
						itemId: 'remove',
						iconCls: 'scalr-menu-icon-delete',
						request: {
							confirmBox: {
								type: 'delete',
								msg: 'Are you sure want to remove team "{name}" ?'
							},
							processBox: {
								type: 'delete'
							},
							url: '/account/teams/xRemove',
							dataHandler: function (record) {
								return { teamId: record.get('id') };
							},
							success: function () {
								store.load();
							}
						}
					}]
				}
			]
		}, {
			itemId: 'view',
			xtype: 'dataview',
		    store: store,
		    tpl: imageTpl,
		    itemSelector: 'div.scalr-ui-account-teams-list',
		    emptyText: 'No teams available',
		    loadMask: false,
		    listeners: {
		    	itemclick: function (view, record, item, index, e, eOpts) {
		    		if (e.getTarget().className == 'scalr-ui-account-teams-list-category-link' || 
		    			e.getTarget().className == 'scalr-ui-account-teams-list-owner-link' || 
		    			e.getTarget().className == 'scalr-ui-account-teams-list-category' || 
		    			e.getTarget().className == 'scalr-ui-account-teams-list-menu-arrow'
		    		) {
		    			var targetParams = {};
		    			targetParams['name'] = e.getTarget().getAttribute('name') ? e.getTarget().getAttribute('name') : '';
		    			targetParams['value'] = e.getTarget().getAttribute('value');
		    			if (e.getTarget().className == 'scalr-ui-account-teams-list-menu-arrow'){
		    				targetParams['left'] = e.getTarget().parentElement.offsetLeft;
		    				targetParams['top'] = e.getTarget().parentElement.offsetTop;
		    			}
		    			else {
		    				targetParams['left'] = e.getTarget().offsetLeft;
		    				targetParams['top'] = e.getTarget().offsetTop;
		    			}
		    			setMenu(record, targetParams);
		    		}
		    	}
		    }
		}],

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
				tooltip: 'Create new team',
				hidden: !moduleParams['teamManage'],
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/account/teams/create');
				}
			}, '->', {
				xtype: 'tbswitchfield',
				hidden: true,
				stateId: 'grid-account-teams-view-switch'
			}]
		}]
	});
	
	return panel;
});
