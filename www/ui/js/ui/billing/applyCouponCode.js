Scalr.regPage('Scalr.ui.billing.applyCouponCode', function (loadParams, moduleParams) {
	
	var form = Ext.create('Ext.form.Panel', {
		bodyCls: 'scalr-ui-frame',
		width: 500,
		bodyPadding: 5,
		title: 'Billing &raquo; Apply coupon code',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},
		scalrOptions: {
			'modal': true
		},
		items: [{
			xtype: 'textfield',
			labelWidth: 80,
			name:'couponCode',
			fieldLabel: 'Coupon code',
			value: ''
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
				text: 'Apply',
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						url: '/billing/xApplyCouponCode/',
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
