Scalr.regPage('Scalr.ui.tools.aws.rds.pg.edit', function (loadParams, moduleParams) {
	function setDesc(field) {
		Ext.each(field.items.getRange(), function(item){
			var el = item.el.down("img.tipHelp");
			new Ext.ToolTip({
				target: el.id,
				dismissDelay: 0,
				html: item.initialConfig.items[1].hText
			});
		});
	}
	form = Ext.create('Ext.form.Panel',{
		bodyCls: 'scalr-ui-frame',
		title: 'Tools &raquo; Amazon Web Services &raquo; Amazon RDS &raquo; Parameter groups &raquo; ' + loadParams['name'] + ' &raquo; Edit',
		width: 900,
		bodyPadding: 5,
		items: [{
			xtype: 'fieldset',
			title: 'General',
			itemId: 'general',
			defaults: {
				labelWidth: 250,
				xtype: 'displayfield',
			},
			items: [{
				name: 'DBParameterGroupName',
				fieldLabel: 'Parameter Group Name',
				value: moduleParams.group.DBParameterGroupName
			},
			{
				fieldLabel: 'Engine',
				name: 'Engine',
				value: moduleParams.group.Engine
			},
			{
				fieldLabel: 'Description',
				name: 'Description',
				value: moduleParams.group.Description
			}]
		},{
			xtype: 'fieldset',
			title: 'System parameters',
			itemId: 'system',
			items: moduleParams.params['system'],
			listeners: {
				afterrender: function(){
					setDesc(this);
				}
			}
		},{
			xtype: 'fieldset',
			title: 'Engine default parameters',
			itemId: 'engine-default',
			items: moduleParams.params['engine-default'],
			listeners: {
				afterrender: function(){
					setDesc(this);
				}
			}
		},{
			xtype: 'fieldset',
			title: 'User parameters',
			itemId: 'user',
			items: moduleParams.params['user'],
			listeners: {
				afterrender: function(){
					setDesc(this);
				}
			}
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
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						form: form.getForm(),
						url: '/tools/aws/rds/pg/xSave',
						params: loadParams,
						success: function (data) {
							Scalr.event.fireEvent('close');
						}
					});
				}
			},{
				xtype: 'button',
				width: 80,
				margin: {
					left: 5
				},
				text: 'Cancel',
				handler: function() {
					Scalr.event.fireEvent('close');
				}
			},{
				xtype: 'button',
				text: 'Reset to defaults',
				width: 95,
				margin: {
					left: 15
				},
				handler: function() {
					Scalr.Request({
						confirmBox: {
							msg: 'Are you sure you want to reset all parameters?',
							type: 'action'
						},
						processBox: {
							type: 'action'
						},
						url: '/tools/aws/rds/pg/xReset',
						params: loadParams,
						success: function (data) {
							document.location.reload();
						}
					});
				}
			}]
		}]
	});
	return form;
});
