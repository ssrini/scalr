Scalr.regPage('Scalr.ui.schedulertasks.create', function (loadParams, moduleParams) {
	var task = {};
	var scriptOptionsValue = {};
	var executionOptions = {};
	if (moduleParams['task']) {
		task = moduleParams['task'];
		executionOptions = moduleParams['task']['config'];
	}
	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 1000,
		title: 'Scheduler tasks &raquo; ' + (moduleParams['task'] ? ('Edit &raquo; ' + moduleParams['task']['name']) : 'Create'),
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side',
			labelWidth: 100
		},

		items: [{
			xtype: 'fieldset',
			title: 'Task',
			items: [{
				xtype: 'hidden',
				name: 'id'
			}, {
				xtype: 'textfield',
				fieldLabel: 'Task name',
				name: 'name',
				allowBlank: false
			}, {
				xtype: 'combo',
				fieldLabel: 'Task type',
				store: [ ['script_exec', 'Execute script'], ['terminate_farm', 'Terminate farm'], ['launch_farm', 'Launch farm']],
				editable: false,
				name: 'type',
				allowBlank: false,
				listeners: {
					change: function (field, newValue, oldValue) {
						if (newValue == 'script_exec') {
							form.down('#farmRoles').enableFarmRoleId = true;
							form.down('#farmRoles').enableServerId = true;
							if (oldValue)
								form.down('#farmRoles').syncItems();
							
							form.down('#executionOptions').show();
							form.down('#terminationOptions').hide();
							form.down('#farmRoles').show();
							
						
						} else if (newValue == 'terminate_farm') {
							form.down('#farmRoles').enableFarmRoleId = false;
							form.down('#farmRoles').enableServerId = false;
							if (oldValue)
								form.down('#farmRoles').syncItems();
							
							form.down('#executionOptions').hide();
							form.down('#scriptOptions').hide();
							form.down('#terminationOptions').show();
							form.down('#farmRoles').show();

						} else if (newValue == 'launch_farm') {
							form.down('#farmRoles').enableFarmRoleId = false;
							form.down('#farmRoles').enableServerId = false;
							if (oldValue)
								form.down('#farmRoles').syncItems();
							
							form.down('#executionOptions').hide();
							form.down('#scriptOptions').hide();
							form.down('#terminationOptions').hide();
							form.down('#farmRoles').show();
						}
					}
				}
			}]
		}, {
			xtype: 'farmroles',
			title: 'Target',
			itemId: 'farmRoles',
			hidden: true,
			params: moduleParams['farmRoles']
		}, {
			xtype: 'fieldset',
			title: 'Task settings',
			items: [{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				items: [{
					xtype: 'displayfield',
					value: 'Start from',
					width: 99
				}, {
					xtype: 'combo',
					store: [ 'Now', 'Specified time' ],
					editable: false,
					queryMode: 'local',
					margin: {
						left: 3
					},
					width: 105,
					value: 'Now',
					name: 'startTimeType',
					listeners: {
						change: function (field, value) {
							if (value == 'Now')
								this.next().hide().disable().next().hide().disable();
							else
								this.next().show().enable().next().show().enable();
						}
					}
				}, {
					xtype: 'datefield',
					name: 'startTimeDate',
					hidden: true,
					disabled: true,
					allowBlank: false,
					format: 'Y-m-d',
					margin: {
						left: 3
					},
					width: 100
				}, {
					xtype: 'timefield',
					name: 'startTimeTime',
					hidden: true,
					disabled: true,
					format: 'H:i',
					margin: {
						left: 3
					},
					value: '00:00',
					width: 80
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: 'and perform task every'
				}, {
					xtype: 'textfield',
					margin: {
						left: 3
					},
					value: '30',
					name: 'restartEvery',
					allowBlank: false,
					width: 40
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: 'minutes till'
				}, {
					xtype: 'combo',
					editable: false,
					queryMode: 'local',
					margin: {
						left: 3
					},
					width: 105,
					store: [ 'Forever', 'Specified time' ],
					value: 'Forever',
					name: 'endTimeType',
					listeners: {
						change: function (field, value) {
							if (value == 'Forever')
								this.next().hide().disable().next().hide().disable();
							else
								this.next().show().enable().next().show().enable();
						}
					}
				}, {
					xtype: 'datefield',
					name: 'endTimeDate',
					hidden: true,
					disabled: true,
					allowBlank: false,
					format: 'Y-m-d',
					margin: {
						left: 3
					},
					width: 100
				}, {
					xtype: 'timefield',
					name: 'endTimeTime',
					hidden: true,
					disabled: true,
					allowBlank: false,
					format: 'H:i',
					margin: {
						left: 3
					},
					value: '00:00',
					width: 80
				}]
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				fieldLabel: 'Priority',
				items: [{
					xtype: 'textfield',
					name: 'orderIndex',
					value: 0,
					width: 60
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
					listeners: {
						'afterrender': function () {
							Ext.create('Ext.tip.ToolTip', {
								target: this.el.down('img.tipHelp'),
								dismissDelay: 0,
								html: '0 - the highest priority'
							});
						}
					}
				}]
			}, {
				xtype: 'combo',
				store: moduleParams['timezones'],
				fieldLabel: 'Timezone',
				queryMode: 'local',
				allowBlank: false,
				name: 'timezone',
				value: moduleParams['defaultTimezone'] || ''
			}]
		}, {
			xtype: 'fieldset',
			title: 'Execution options',
			itemId: 'executionOptions',
			hidden: true,
			items: [{
				xtype: 'combo',
				fieldLabel: 'Script',
				name: 'scriptId',
				store: {
					fields: [ 'id', 'name', 'description', 'issync', 'timeout', 'revisions' ],
					data: moduleParams['scripts'],
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				emptyText: 'Select a script',
				editable: false,
				queryMode: 'local',
				listeners: {
					change: function (field, value) {
						var cont = field.up(), r = field.store.findRecord('id', value), fR = cont.down('[name="scriptVersion"]');

						fR.store.load({ data: r.get('revisions')} );
						fR.store.sort('revision', 'DESC');
						fR.setValue(fR.store.getAt(0).get('revision'));
						cont.down('[name="scriptTimeout"]').setValue(r.get('timeout'));
						cont.down('[name="scriptIsSync"]').setValue(r.get('issync'));
					}
				}
			}, {
				xtype: 'combo',
				store: [ ['1', 'Synchronous'], ['0', 'Asynchronous']],
				editable: false,
				queryMode: 'local',
				name: 'scriptIsSync',
				fieldLabel: 'Execution mode'
			}, {
				xtype: 'textfield',
				fieldLabel: 'Timeout',
				name: 'scriptTimeout'
			},{
				xtype: 'combo',
				store: {
					fields: [{ name: 'revision', type: 'int' }, 'fields' ],
					proxy: 'object'
				},
				valueField: 'revision',
				displayField: 'revision',
				editable: false,
				queryMode: 'local',
				name: 'scriptVersion',
				fieldLabel: 'Version',
				listeners: {
					change: function (field, value) {
						var f = form.down('[name="scriptVersion"]');
						var r = f.store.findRecord('revision', f.getValue());
						var fields = r.get('fields'), fieldset = form.down('#scriptOptions');
						
						Ext.each(fieldset.items.getRange(), function(item) {
							scriptOptionsValue[item.name] = item.getValue();
						});
						fieldset.removeAll();
						if (Ext.isObject(fields)) {
							for (var i in fields) {
								fieldset.add({
									xtype: 'textfield',
									fieldLabel: fields[i],
									name: 'scriptOptions[' + i + ']',
									value: scriptOptionsValue['scriptOptions[' + i + ']'] ? scriptOptionsValue['scriptOptions[' + i + ']'] : '',
									width: 300
								});
							}
							fieldset.show();
						} else {
							fieldset.hide();
						}
					}
				}
			}]
		}, {
			xtype: 'fieldset',
			title: 'Script options',
			itemId: 'scriptOptions',
			labelWidth: 100,
			hidden: true,
			fieldDefaults: {
				width: 150
			}
		}, {
			xtype: 'fieldset',
			title: 'Termination options',
			itemId: 'terminationOptions',
			hidden: true,
			items: [{
				xtype: 'checkbox',
				boxLabel: 'Delete DNS zone from nameservers. It will be recreated when the farm is launched.',
				inputValue: 1,
				name: 'deleteDNSZones',
				checked: task['config'] ? (task['config']['deleteDNSZones'] ? task['config']['deleteDNSZones'] : false) : false
			}, {
				xtype: 'checkbox',
				boxLabel: 'Delete cloud objects (EBS, Elastic IPs, etc)',
				inputValue: 1,
				name: 'deleteCloudObjects',
				checked: task['config'] ? (task['config']['deleteCloudObjects'] ? task['config']['deleteCloudObjects'] : false): false
			}]
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
				text: task ? 'Save' : 'Create',
				width: 80,
				handler: function() {
					var task = {}, values = form.getForm().getValues();

					if (form.getForm().isValid()) {
						if (values['startTimeType'] == 'Now')
							task['startTime'] = '';
						else {
							task['startTime'] = values['startTimeDate'] + ' ' + values['startTimeTime'] + ':00';
						}
							
						if (values['endTimeType'] == 'Forever')
							task['endTime'] = '';
						else {
							task['endTime'] = values['endTimeDate'] + ' ' + values['endTimeTime'] + ':00';
						}

						Scalr.Request({
							processBox: {
								type: 'save'
							},
							form: form.getForm(),
							url: '/schedulertasks/xSave/',
							params: task,
							success: function () {
								Scalr.event.fireEvent('redirect', '#/schedulertasks/view', true);
							}
						});
					}
				}
			}, {
				xtype: 'button',
				width: 80,
				margin: {
					left: 5
				},
				text: 'Cancel',
				handler: function() {
					Scalr.event.fireEvent('close');
				}
			}]
		}]
	});

	if (task) {
		form.getForm().setValues(task);
		if (task['startTime']) {
			form.getForm().setValues({
				startTimeType: 'Specified time',
				startTimeDate: task['startTime'].split(' ')[0],
				startTimeTime: task['startTime'].split(' ')[1]
			});
		}
		if (task['endTime']) {
			form.getForm().setValues({
				endTimeType: 'Specified time',
				endTimeDate: task['endTime'].split(' ')[0],
				endTimeTime: task['endTime'].split(' ')[1]
			});
		}
		if (executionOptions['scriptId']) {
			for (var i in executionOptions['scriptOptions']) {
				scriptOptionsValue['scriptOptions[' + i + ']'] = executionOptions['scriptOptions'][i];
			}
			form.getForm().setValues(executionOptions);
		}
	}

	return form;
});
