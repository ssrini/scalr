Scalr.regPage('Scalr.ui.tools.aws.ec2.elb.instanceHealth', function(loadParams, moduleParams) {
	var form = Ext.create('Ext.form.Panel', {
		tools: [{
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}],
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 650,
		title: 'Instance Health',
		items: [{
			labelWidth: 63,
			xtype: 'displayfield',
			fieldLabel: 'State',
			value: moduleParams['State']
		},{
			data: moduleParams,
			xtype: 'displayfield',
			value: moduleParams['Description'],
			tpl: new Ext.XTemplate('Description: <tpl if="this.State==\'OutOfService\'"><font color = "red">{Description}</tpl><tpl if="this.State==\'InService\'"><font color = "black">{Description}</tpl>',
			{State: moduleParams.State})
		}],
		dockedItems:[{
			xtype: 'container',
			dock: 'bottom',
			cls: 'scalr-ui-docked-bottombar',
			layout: {
				type: 'hbox',
				pack: 'center'
			},
			items: [{			
				xtype: 'button',
				text: 'Derigister instance from the load balancer',
				handler: function(){
					Scalr.Request({
						processBox: {
							type: 'delete'
						},
						url: '/tools/aws/ec2/elb/'+ loadParams['elbName'] +'/xDeregisterInstance',
						params: loadParams,
						success: function () {
							Scalr.event.fireEvent('close');
						}
					});
				}
			}]

		}]
	});
	return form;
});