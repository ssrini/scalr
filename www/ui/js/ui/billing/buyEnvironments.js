Scalr.regPage('Scalr.ui.billing.buyEnvironments', function (loadParams, moduleParams) {
	
	var form = Ext.create('Ext.form.Panel', {
		bodyCls: 'scalr-ui-frame',
		width: 500,
		bodyPadding: 5,
		title: 'Billing &raquo; Buy more environments',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},
		scalrOptions: {
			'modal': true
		},
		items: [{
			xtype: 'fieldcontainer',
			layout: 'hbox',
			hideLabel: true,
			items:[{
				xtype: 'displayfield',
				margin: {
					right: 3
				},
				value: 'Set my environments limit to '	
			}, {
				xtype: 'textfield',
				validator:function(value) {
					return (value < 0 || value > 100) ? "Amount of environments should be in range between 1 and 99" : true;
				},
				width: 50,
				hidelabel: true,
				name: 'amount',
				value: 1,
				listeners: {
					change: function()
					{
						var sum = parseInt((parseInt(this.getValue())-1)*99);
						if (!sum || sum < 0) sum = 0;
						form.down("#charge").setValue('In addition to your current mothly payment, you will be charged for $'+sum+' / month');
					}
				}
			}, {
				xtype: 'displayfield',
				margin: {
					left: 3
				},
				value: 'environments'	
			}]
		}, {
			xtype: 'displayfield',
			itemId: 'charge',
			hideLabel:true,
			value: 'In addition to your current mothly payment, you will be charged for $'+((moduleParams['currentLimit']-1)*99)+' / month'
		}],
		tools: [{
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}],
		
		dockedItems: [{
			xtype: 'container',
			cls: 'scalr-ui-docked-bottombar',
			dock: 'bottom',
			layout: {
				type: 'hbox',
				pack: 'center'
			},
			items: [{
				xtype: 'button',
				text: 'Proceed',
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						url: '/billing/xBuyEnvironments/',
						form: this.up('form').getForm(),
						success: function () {
							Scalr.message.Success("Request for changing environments limit successfully sent. Limit will be extended within 60 minutes.");
							Scalr.event.fireEvent('close');
						}
					});
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

	return form;
});
