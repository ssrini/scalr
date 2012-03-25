Scalr.regPage('Scalr.ui.billing.reactivate', function (loadParams, moduleParams) {
	
	var form = Ext.create('Ext.form.Panel', {
		bodyCls: 'scalr-ui-frame',
		width: 500,
		bodyPadding: 5,
		title: 'Billing &raquo; Reactivate subscription',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},
		scalrOptions: {
			'modal': false
		},
		items: [{xtype:'component', html:"<b>What's going to happen?</b></br></br>"+
			"&bull;&nbsp;This subscription will be immediately activated</br>"+
			"&bull;&nbsp;You will be charged $"+moduleParams['billing']['productPrice']+" for "+moduleParams['billing']['productName']+" package</br>"+
			"&bull;&nbsp;Your's billing date will be reset to today.</br></br>"+
			"There is no 'undo' for this operation, so please be sure before you press the 'Reactivate Subscription' button."
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
				text: 'Reactivate Subscription',
				width: 160,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						url: '/billing/xReactivate/',
						form: this.up('form').getForm(),
						success: function () {
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
