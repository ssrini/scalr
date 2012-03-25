Scalr.regPage('Scalr.ui.account.teams.list', function (loadParams, moduleParams) {
	var menu = Ext.create('Ext.menu.Menu', {
	    hidden: true
	});
	var store = Ext.create('store.store', {
		fields: [
			'id', 'name', 'owner', 'ownerId', 'teamOwner', 'users', 'email', 'fullname', 'type', 'description', 'envs', 'permissions'
		],
		proxy: {
			type: 'scalr.paging',
			url: '/account/teams/xListTeamsAndUsers'
		},
		remoteSort: true
	});
	store.load();
	var imageTpl = new Ext.XTemplate(
    	'<tpl for=".">',
	        '<div class = "scalr-ui-account-teams-list" style="',
	        	'<tpl if="xindex%2==0">background-color: #F3F7FC;</tpl>',
	        	'">',
	        	'<div class = "block" style = "width: 40%;">',
	          		'<span class = "title">{name}&nbsp;<a href="#/account/teams/{id}/edit"><div class = "scalr-menu-icon-configure" style = "display: inline-block;"></div></a></span>',
	          		'<br/><span class="desc">&nbsp;{description}</span>',
	          		'<div>',
	          			'<div class = "scalr-menu-icon-environment" style = "display: inline-block;"></div>',
	          			'<tpl for="envs">',
		          			'<a href = "#/environments/{id}/edit" class = "button">',
			          			'<div style = "background-color: #4483D0;">',
			          				'{name}',
			          			'</div>',
		          			'</a>',
		          		'</tpl>',
	          		'</div>',
	          	'</div>',
	          	'<div class="block" style = "width: 57%;">',
		          	'<div class = "groups">',
			          	'<div class="category"> Users<div class="menu-arrow" value = "users"></div>',
			          	'</div>', 
			          	'<div style="padding-left: 135px; margin-top: -15px;" class="owner"><a value={ownerId} class="link" name="Owner">{owner}<div class="menu-arrow"></div></a>',
			          	'<tpl for="users">',
			          	'<div class="users">',
			          	'<a class = "category-link" name="User" value={id}>{email}<div class="menu-arrow"></div></a>',
			          	'</div>',
			          	'</tpl>',
			          	'</div>',
		          	'</div>',
		          	'<div class = "groups">',
			          	'<div class="category"> Permission groups<div class="menu-arrow"></div>',
			          	'</div>',
			          	'<div style="padding-left: 135px; margin-top: -15px;"> ',
			          	 	'<tpl for="permissions">',
			          	 	'<div class="permissions">',
			          		'<a value={id} class = "category-link" name="Group">{name}<div class="menu-arrow" value = "permissions"></div></a>',
			          		'</div>',
			          		'</tpl>',
			          	'</div>',
		          	'</div>',
	          	'</div>',
	        '</div>',
	    '</tpl>'
	);
	panel = Ext.create('Ext.panel.Panel', {
		title: 'Account &raquo; Teams &raquo; List',
		scalrOptions: {
			maximize: 'all'
		},
		items: {
			xtype: 'dataview',
		    store: store,
		    tpl: imageTpl,
		    itemSelector: 'div.scalr-ui-account-teams-list',
		    emptyText: 'No teams available',
		    listeners: {
		    	itemclick: function (view, record, item, index, e, eOpts) {
		    		//var r = record.get('permissions');
		    		//console.log(store.getAt(index).add({permissions: {id: 13, name: 'dfsdf'}}));
		    		//store.add({permissions: {id: 13, name: 'dfsdf'}});
		    		//r.add({id: 13, name: 'dfsdf'});
		    		//record.set();
		    		if(e.getTarget('div.menu-arrow') && !e.getTarget('a')){
		    			menu.removeAll();
		    			menu.add({
					    	iconCls: 'scalr-menu-icon-edit',
					        text: 'Create new',
					        listeners:{
					        	click: function(){
					        		if(e.getTarget('div.menu-arrow').getAttribute('value') == 'users')
					        			Scalr.event.fireEvent('redirect', '#/account/users/create');
					        		else {
					        			Scalr.Request({
											confirmBox: {
												title: 'Create new permission group',
												form: {
													xtype: 'textfield',
													fieldLabel: 'Name',
													name: 'name',
													allowBlank: false
												},
												formValidate: true
											},
											processBox: {
												type: 'save'
											},
											url: '/account/teams/' + record.get('id') + '/xCreatePermissionGroup/',
											success: function (data) {
												store.load();
											}
										});
					        		}
					        	}
					        }
					    });
					    menu.setPosition(e.target.offsetLeft+10, e.target.offsetTop+85);
		    			menu.show();
		    		}
		    		if(e.getTarget('a') && e.getTarget('a').getAttribute('value')){
		    			var target = e.getTarget('a');
		    			menu.removeAll();
		    			menu.add({
					    	iconCls: 'scalr-menu-icon-edit',
					        text: 'Edit ' + target.getAttribute('name'),
					        href: (target.getAttribute('name') == 'User' || target.getAttribute('name') == 'Owner') ? '#/account/users/' + target.getAttribute('value') + '/edit' : '#/account/teams/' + record.get('id') + '/permissionGroup?groupId=' + target.getAttribute('value')
					    }, target.getAttribute('name') == 'User' ? {
		    				text: 'Edit Permissions',
		    				listeners: {
					        	click: function () {
					        		var groups = [];
					        		Ext.each (record.get('permissions'), function (rec) {
					        			var isChecked = false;
					        			Ext.each (record.get('users'), function (user) {
											if(user.id == target.getAttribute('value')){
												console.log(user.id);
												Ext.each(user.groups, function (group) {
													if(group.id == rec.id)
														isChecked = true;
												});
											}
										});
					        			groups.push({
											xtype: 'checkbox',
											boxLabel: rec.name,
											inputValue: rec.id,
											name: rec.id,
											checked: isChecked
										});
									});
					        		Scalr.Request({
										confirmBox: {
											title: 'Manage Permissions',
											form: [{
												xtype: 'radiofield',
												name: 'permission',
												checked: true,
												boxLabel: 'Full'
											},{
												xtype: 'radiofield',
												name: 'permission',
												boxLabel: 'Groups',
												checked: false,
												listeners: {
													change: function(field, newValue, oldValue, eOpts) {
														if(newValue)
															field.next('#groups').show();
														else
															field.next('#groups').hide();
													}
												}
											},{
												hidden: true,
												itemId: 'groups',
	       										xtype: 'checkboxgroup',
										        columns: 3,
										        vertical: true,
										        items: groups,
											}]
										},
										processBox: {
											msg: 'Updating...',
											type: 'action'
										},
										scope: this,
										success: function (data, response, options){
											//store.load();
										}
									});
					        	}
					       	}
		    			} : '',{
					    	xtype: 'menuseparator'
					    },
					    target.getAttribute('name') == 'Owner' ? '' : {
					        text: (target.getAttribute('name') == 'Group') ? 'Remove From Team' : 'Remove',
					        iconCls: 'scalr-menu-icon-delete',
					        listeners: {
					        	click: function () {
					        		Scalr.Request({
										confirmBox: {
											msg: 'Remove ' + target.getAttribute('name') + '?',
											type: 'delete'
										},
										processBox: {
											msg: 'Removing...',
											type: 'delete'
										},
										scope: this,
										url: (target.getAttribute('name') == 'Group') ? '/account/teams/xRemovePermissionGroup' : '/account/users/xRemove',
										params: (target.getAttribute('name') == 'Group') ? {groupId: target.getAttribute('value'), teamId: record.get('id')} : {userId: target.getAttribute('value')},
										success: function (data, response, options){
											store.load();
										}
									});
					        	}
					        }
		    			});
		    			menu.setPosition(e.target.offsetLeft+10, e.target.offsetTop+85);
		    			menu.show();
		    		}
		    	}
		    }
		}
	});
	return panel;
});