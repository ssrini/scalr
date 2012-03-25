Scalr.regPage('Scalr.ui.farms.builder.tabs.chef', function () {	
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Chef settings',
		bodyPadding: 5,
		
		getDefaultValues: function (record) {
			return {};
		},
		
		beforeShowTab: function (record, handler) {
			var chefId = record.get('settings')['chef.runlist_id'];
			this.down('[name="enableChef"]').setValue(!Ext.isEmpty(chefId));
			
			var gridPanel = this.down('#runList');
			gridPanel.getSelectionModel().deselectAll();
			this.down('#runList').store.load(function (records, operations, success){
				for (i = 0; i<records.length; i++) {
					if (records[i].get('id') == chefId) {
						gridPanel.getSelectionModel().select(records[i].index);
						records[i].set('sel', 1);
					}
				}
			});
			Scalr.event.on('update', function (target, runList, type) {
				if (type == 'update') {
					Ext.each(this.down('#runList').store.getRange(), function(item){
						if(item.get('id') == runList.id)
							item.set(runList);
					});
				}
				if (type == 'create')
					this.down('#runList').store.add(runList); 
			}, this);
		},
		
		showTab: function (record) {
			
		},
		
		isEnabled: function (record) {
			return record.get('behaviors').match('chef');
		},
		
		hideTab: function (record) {
			var settings = record.get('settings');
			settings['chef.runlist_id'] = '';
			settings['chef.attributes'] = '';
			this.down('#runList').store.each(function(item){
				if (item.get('sel')) {
					settings['chef.runlist_id'] = item.get('id');
					var data = {};
					Ext.each(Ext.decode(item.get('attributes')), function(attribItem){
						data[attribItem.name] = attribItem.value;
					}); 
					settings['chef.attributes'] = Ext.encode(data);
				}
			});
			this.down('#attrib').down('#optionsView').store.removeAll();
			/*if (this.down('[name="enableChef"]').getValue() && this.down('#runList').getSelectionModel().getSelection()[0]) {
				var data = {};
				Ext.each(Ext.decode(this.down('#runList').getSelectionModel().getSelection()[0].get('attributes')), function(item){
					data[item.name] = item.value;
				}); 
				settings['chef.runlist_id'] = this.down('#runList').getSelectionModel().getSelection()[0].get('id');
				settings['chef.attributes'] = Ext.encode(data);
			} else {}*/

			
			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			title: 'Enable Chef',
			checkboxToggle: true,
			checkboxName: 'enableChef',
			bodyPadding: 5,
			layout: {
				type: 'hbox'
			},
			defaults: {
				width: 620,
				height: 600,
				margin: {
					bottom: 5
				}
			},
			items: [{
				xtype: 'gridpanel',
				title: 'RunLists',
				itemId: 'runList',
				plugins: {
					ptype: 'gridstore'
				},
				viewConfig: {
					emptyText: 'No RunList found'
				},
				store:{
					fields: ['id', 'name', 'description', 'attributes', 'chefEnv', 'sel'],
					proxy: {
						type: 'ajax',
						reader: {
							type: 'json',
							root: 'data'
						},
						url: 'services/chef/xListRunList'
					}
				},
				columns: [{
					text: '',
					width: 35,
					xtype: 'templatecolumn',
					tpl: '<div class="<tpl if="sel==1">x-form-cb-checked</tpl>" style="text-align: center"><input type="button" class="x-form-field x-form-radio selectedRunlist"></div>'
				}, {
					text: 'Name',
					dataIndex: 'name',
					flex: 1
				},{
					text: 'Description',
					flex: 2,
					dataIndex: 'description'
				},{
					text: 'Chef environment',
					flex: 2,
					dataIndex: 'chefEnv'
				},{
					xtype: 'optionscolumn',
					optionsMenu: [{ 
						text:'Edit', 
						iconCls: 'scalr-menu-icon-edit',
						menuHandler: function(item) {
							Scalr.event.fireEvent('redirect','/#/services/chef/runlists/edit?runlistId=' + item.record.get('id'));
						}
					},{
						xtype: 'menuseparator',
						itemId: 'option.attachSep'
					},{ 
						text:'Source', 
						iconCls: 'scalr-menu-icon-info',
						menuHandler: function(item) {
							Scalr.event.fireEvent('redirect','/#/services/chef/runlists/source?runlistId=' + item.record.get('id'));
						}
					}],
					getVisibility: function (record) {
						return true;
					}
				}],
				listeners: {
					select: function(rowModel, record) {
						this.next('#attrib').down('#optionsView').store.removeAll();
						if(record.get('attributes'))
							this.next('#attrib').down('#optionsView').store.add(Ext.decode(record.get('attributes')));
					},
					itemclick: function (view, record, item, index, e) {
						if (e.getTarget('input.selectedRunlist')) {
							if (record.get('sel') != 1) {
								view.store.each(function(record) {
									if (record.get('sel') == 1)
										record.set('sel', 0);
								});
								
								record.set('sel', 1);
							}
						}
					}
				},
				dockedItems: [{
					xtype: 'toolbar',
					dock: 'top',
					layout: {
						type: 'hbox', 
						align: 'left',
						pack: 'start'
					},
					items: [{
						icon: '/ui/images/icons/add_icon_16x16.png',
						cls: 'x-btn-icon',
						tooltip: 'Add new RunList',
						listeners: {
							click: function() {
								Scalr.event.fireEvent('redirect','/#/services/chef/runlists/create');
							}
						}
					}]
				}]
			},{
				xtype: 'fieldset',
				title: 'Attributes',
				itemId: 'attrib',
				margin: {
					left: 5
				},
				flex: 1,
				layout: {
					type: 'vbox',
					align: 'stretch'
				},
				paramAddReset: function() {
					var data = [];
					var fieldset = this;
					Ext.each(fieldset.down('#optionsView').store.getRange(), function(item){
						data.push({name: item.get('name'), type: item.get('type'), value: item.get('value')});
					});
					fieldset.up().down('#runList').getSelectionModel().getSelection()[0].set('attributes', Ext.encode(data));
					Scalr.Request({
						url: 'services/chef/runlists/xUpdateAttrib',
						params: {runlistId: fieldset.up().down('#runList').getSelectionModel().getSelection()[0].get('id'), attributes: fieldset.up().down('#runList').getSelectionModel().getSelection()[0].get('attributes')}
					});
					
			
					fieldset.down('#fieldname').reset();
					fieldset.down('#fielddefvalTextarea').reset();
					fieldset.down('#fielddefvalTextfield').reset();
			
					fieldset.down('#paramAdd').show();
					fieldset.down('#paramSave').hide();
					fieldset.down('#paramDelete').hide();
					fieldset.down('#paramCancel').hide();
				}, 
				paramGetValue: function() {
					var data = {}, valid = true;
					var fieldset = this;
					data['name'] = fieldset.down('#fieldname').getValue();
					data['type'] = fieldset.down('#fieldtype').getValue();
			
					valid = fieldset.down('#fieldname').isValid() && valid; 
					valid = fieldset.down('#fieldtype').isValid() && valid;
			
					if (! valid)
						return;
			
					if (data['type'] == 'text')
						data['value'] = fieldset.down('#fielddefvalTextfield').getValue();
					if (data['type'] == 'textarea')
						data['value'] = fieldset.down('#fielddefvalTextarea').getValue();
					return data;
				},
				items: [{
					xtype: 'fieldset',
					title: 'Details',
					autoHeight: true,
					bodyPadding: 5,
					defaults: {
						anchor: '100%',
						labelWidth: 80
					},
					items: [{
						xtype: 'combo',
						fieldLabel: 'Type',
						store: [['textarea', 'Textarea'],['text', 'Text']],
						allowBlank: false,
						editable: false,
						itemId: 'fieldtype',
						queryMode: 'local',
						value: 'textarea',
						listeners: {
							change: function (field) {
								field.next('#fielddefvalTextfield').hide().disable();
								field.next('#fielddefvalTextarea').hide().disable();
	
								if (this.getValue() == 'text')
									field.next('#fielddefvalTextfield').show().enable();
	
								if (this.getValue() == 'textarea')
									field.next('#fielddefvalTextarea').show().enable();
							}
						}
					}, {
						xtype: 'textfield',
						itemId: 'fieldname',
						fieldLabel: 'Name',
						allowBlank: false
					}, {
						xtype: 'textfield',
						fieldLabel: 'Value',
						itemId: 'fielddefvalTextfield',
						hidden: true,
					}, {
						xtype: 'textarea',
						fieldLabel: 'Value',
						itemId: 'fielddefvalTextarea'
					}, {
						layout: 'column',
						bodyCls: 'scalr-ui-frame',
						border: false,
						margin: {
							bottom: 5
						},
						items: [{
							text: 'Add',
							itemId: 'paramAdd',
							xtype: 'button',
							width: 70,
							handler: function () {
								if (this.up('#attrib').up().down('#runList').getSelectionModel().getSelection()[0]) {
									var upPanel = this.up('#attrib');
									var data = upPanel.paramGetValue();
	
									if (Ext.isObject(data)) {
										if (upPanel.down('#optionsView').store.findExact('name', data['name']) == -1) {
											upPanel.down('#optionsView').store.add(data);
											upPanel.paramAddReset();
										} else
											upPanel.down('#fieldname').markInvalid('Such param name already exist');
									}
								}
							}
						}, {
							text: 'Change',
							itemId: 'paramSave',
							xtype: 'button',
							hidden: true,
							width: 70,
							handler: function () {
								var upPanel = this.up('#attrib');
								var records = upPanel.down('#optionsView').getSelectionModel().getSelection(), data = upPanel.paramGetValue();
	
								if (Ext.isObject(data) && records[0]) {
									for (i in data)
										records[0].set(i, data[i]);
								}
								
								upPanel.down('#optionsView').getSelectionModel().deselectAll();
							}
						}, {
							text: 'Cancel',
							margin: {
								left: 5
							},
							itemId: 'paramCancel',
							xtype: 'button',
							hidden: true,
							width: 70,
							handler: function () {
								var upPanel = this.up('#attrib');
								upPanel.down('#optionsView').getSelectionModel().deselectAll();
							}
						}, {
							text: 'Delete',
							margin: {
								left: 5
							},
							itemId: 'paramDelete',
							xtype: 'button',
							hidden: true,
							width: 70,
							handler: function () {
								var upPanel = this.up('#attrib');
								var view = upPanel.down('#optionsView'), records = view.getSelectionModel().getSelection();
								if (records[0]) {
									view.store.remove(records[0]);
									view.getSelectionModel().deselectAll();
								}
							}
						}]
					}]
				},{
					xtype: 'grid',
					itemId: 'optionsView',
					height: '64%',
					store: [],
					singleSelect: true,
					plugins: {
						ptype: 'gridstore'
					},
					viewConfig: {
						emptyText: 'No attributes found'
					},
					columns: [
						{ text: 'Name', flex: 1, dataIndex: 'name', sortable: true },
						{ text: 'Value', flex: 1, dataIndex: 'value', sortable: true },
						{ text: 'Type', flex: 1, dataIndex: 'type', sortable: true }
					],
					listeners: {
						afterrender: function () {
							this.headerCt.el.applyStyles('border-left-width: 1px !important');
						},
						selectionchange: function(c, selections) {
							var upPanel = this.up('#attrib');
							if (selections.length) {
								var rec = selections[0];
		
								upPanel.down('#fieldtype').setValue(rec.get('type'));
								upPanel.down('#fieldname').setValue(rec.get('name'));
		
								if (rec.get('type') == 'text')
									upPanel.down('#fielddefvalTextfield').setValue(rec.get('value'));
		
								if (rec.get('type') == 'textarea')
									upPanel.down('#fielddefvalTextarea').setValue(rec.get('value'));
		
								upPanel.down('#paramAdd').hide();
								upPanel.down('#paramSave').show();
								upPanel.down('#paramCancel').show();
								upPanel.down('#paramDelete').show();
							} else
								upPanel.paramAddReset();
						}
					}
				}] 
			}]
		}]
	});
});