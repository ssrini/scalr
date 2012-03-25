Scalr.regPage('Scalr.ui.dashboard.view', function (loadParams, moduleParams) {
	function addWidgetForm () {// function for add Widget panel
		var widgetForm = new Ext.form.FieldSet({
			title: 'Widgets list',
			items: {
				xtype: 'checkboxgroup',
				columns: 2,
				vertical: true,
				name: 'widgets'
			}
		});
		var widgets = [
			{name: 'dashboard.billing', title: 'Billing', desc: 'Displays your current billing parameters'},
			{name: 'dashboard.announcement', title: 'Announcement', desc: 'Displays last 10 news from The Official Scalr blog'},
			{name: 'dashboard.lasterrors', title: 'Last errors', desc: 'Displays last 10 errors from system logs'},
			{name: 'dashboard.usagelaststat', title: 'Usage statistic', desc: 'Displays total spent money for this and last months'}
		];
		for (var i = 0; i < widgets.length; i++) {
			widgetForm.down('checkboxgroup').add({
				xtype: 'container',
				layout: 'hbox',
				items: [{
					xtype: 'checkbox',
					boxLabel: widgets[i]['title'],
					name: 'widgets',
					inputValue: widgets[i]['name']
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
					description: widgets[i]['desc'],
					listeners: {
						afterrender: function () {
							Ext.create('Ext.tip.ToolTip', {
								target: this.el.down('img.tipHelp'),
								dismissDelay: 0,
								html: this.description
							});
						}
					}
				}]
			});
		}
		return widgetForm;
	}

	var panel = Ext.create('Ext.ui.dashboard.Panel',{
		title: 'Dashboard widgets',
		defaultType: 'dashboard.column',

		scalrOptions: {
			'maximize': 'all',
			'reload': false
		},
		dockedItems: [{
			xtype: 'toolbar',
			height: 27,
			dock: 'top',
			items: [{
				iconCls: 'scalr-ui-dashboard-icon-add-column',
				cls: 'x-btn-icon',
				tooltip: 'Add more column',
				handler: function() {
					if(panel.items.length < 5) {
						panel.newCol();
						panel.showEditPanel();
					}
				}
			}, {
				iconCls: 'scalr-ui-dashboard-icon-add-widget',
				cls: 'x-btn-icon',
				tooltip: 'Add more widget(s)',
				handler: function() {
					Scalr.Confirm ({
						title: 'Select widgets to add',
						form: addWidgetForm(),
						ok: 'Add',
						scope: this,
						success: function(formValues) {
							if (!panel.items.length)
								panel.newCol();
							if (Ext.isArray(formValues.widgets)) {
								for(var i = 0; i < formValues.widgets.length; i++) {
									panel.items.getAt(0).add(panel.newWidget(formValues.widgets[i]));
								}
							} else
								panel.items.getAt(0).add(panel.newWidget(formValues.widgets));
							panel.showEditPanel();
							panel.syncAutoUpdate();
						}
					});
				}
			}, {
				xtype: 'button',
				iconCls: 'scalr-ui-dashboard-icon-save-panel',
				cls: 'x-btn-icon',
				itemId: 'saveButton',
				hidden: true,
				tooltip: 'Save current widgets',
				handler: function() {
					var data = []; //cols
					var i = 0;
					panel.items.each(function(column){
						var col = {widgets: []}; //widgets
						column.items.each(function(item){
							var test = {params: item.params, name: item.xtype, url: ''};
							col['widgets'].push(test);
						});
						data[i] = col;
						i++;
					});
					Scalr.Request({
						url: 'dashboard/xSavePanel',
						params: {panel: Ext.encode(data)},
						success: function(data) {
							moduleParams['panel'] = data.data;
						}
					});
					this.hide();
					panel.down('#cancelButton').hide();
				}
			}, {
				xtype: 'button',
				iconCls: 'scalr-ui-dashboard-icon-undo-panel',
				cls: 'x-btn-icon',
				tooltip: 'Cancel unsaved changes',
				itemId: 'cancelButton',
				hidden: true,
				handler: function() {
					panel.removeAll();
					panel.fillDash();
					this.hide();
					panel.down('#saveButton').hide();
				}
			}]
		}],

		syncAutoUpdate: function (restart) {
			var widgets = {};
			restart = restart || 'yes';
			this.items.each(function (column) {
				column.items.each(function (widget) {
					if (widget.widgetType == 'local')
						widgets[widget.id] = {
							name: widget.xtype,
							params: widget.widgetParams || {}
						};
				});
			});

			Scalr.timeoutHandler.params['updateDashboard'] = Ext.encode(widgets);

			if (restart == 'yes')
				Scalr.timeoutHandler.restart();
		},

		fillDash: function () { // function for big panel
			for (var i = 0; i < moduleParams['panel'].length; i++) {
				this.newCol();
				for(var j = 0; j < moduleParams['panel'][i].widgets.length; j++) {
					var w = this.items.getAt(i).add(
						this.newWidget(moduleParams['panel'][i]['widgets'][j]['name'], moduleParams['panel'][i]['widgets'][j]['params'])
					);

					if (w.widgetType == 'local' && w.widgetUpdate)
						w.widgetUpdate(moduleParams['panel'][i]['widgets'][j]['widgetContent'] || {});
				}
			}
		},
		listeners: {
			render: function() {
				panel.body.on('mouseover', function(e, el, obj) {
					if (e.getTarget('.scalr-ui-dashboard-container') && !e.getTarget('.scalr-ui-dashboard-container-wait') && !panel.down('[id='+e.getTarget('.scalr-ui-dashboard-container').id+']').items.length) {
						Ext.fly(e.getTarget('.scalr-ui-dashboard-container')).addCls('scalr-ui-dashboard-container-empty');
					}
				});
				panel.body.on('mouseout', function(e, el, obj) {
					if (e.getTarget('.scalr-ui-dashboard-container-empty')) {
						Ext.fly(e.getTarget('.scalr-ui-dashboard-container')).removeCls('scalr-ui-dashboard-container-empty');
					}
				});
				panel.body.on('click', function(e, el, obj) {
					if (e.getTarget('div.remove')) {
						Scalr.Confirm ({
							title: 'Select widgets to add',
							msg: 'Remove this column?',
							type: 'delete',
							scope: panel.down('[id='+e.getTarget('.scalr-ui-dashboard-container').id+']'),
							success: function(data) {
								if (!this.items.length) {
									panel.remove(this);
									panel.showEditPanel();
								}
							}
						});
					}
				});
			}
		}
	});
	if (moduleParams['panel'] && moduleParams['panel'].length) {
		panel.fillDash();
		panel.syncAutoUpdate('no');
	} else {
		panel.newCol();
	}
	Scalr.event.on('update', function (type, data) {
		if (type == 'lifeCycle' && data.updateDashboard) {
			for (var i in data.updateDashboard) {
				try {
					panel.down('#' + i).widgetUpdate(data.updateDashboard[i]);
				} catch (e) { }
			}
		}
		if (type == '/dashboard/update') {
			moduleParams[panel] = data;
			panel.fillDash();
		}
	});
	return panel;
});
