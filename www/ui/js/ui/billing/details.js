Scalr.regPage('Scalr.ui.billing.details', function (loadParams, moduleParams) {
	var applyLimit = function () {
		var limit = this.limit['limit'], usage = this.limit['usage'];
		var color = 'green';
		if (limit != -1) {
			if (usage > limit)
				color = 'red';
			else if ((limit - usage) <= Math.ceil(limit * 0.1))
				color = 'yellow';
		}

		this.setValue("<span style='color:green;'>" + usage + "</span> of "+ ((limit == -1) ? "Unlimited" : limit));

		this.inputEl.applyStyles("padding-bottom: 3px; padding-left: 5px");
		this.el.applyStyles("background: -webkit-gradient(linear, left top, left bottom, from(#C8D6E5), to(#DAE5F4));");
		this.el.applyStyles("background: -moz-linear-gradient(top, #C8D6E5, #DAE5F4);");

		if (color == 'red') {
			this.bodyEl.applyStyles("background: -webkit-gradient(linear, left top, left bottom, from(#F4CDCC), to(#E78B84))");
			this.bodyEl.applyStyles("background: -moz-linear-gradient(top, #F4CDCC, #E78B84)");
		} else if (color == 'yellow') {
			this.bodyEl.applyStyles("background: -webkit-gradient(linear, left top, left bottom, from(#FCFACB), to(#F3C472))");
			this.bodyEl.applyStyles("background: -moz-linear-gradient(top, #FCFACB, #F3C472)");
		} else {
			this.bodyEl.applyStyles("background: -webkit-gradient(linear, left top, left bottom, from(#C5E1D9), to(#96CFAF))");
			this.bodyEl.applyStyles("background: -moz-linear-gradient(top, #C5E1D9, #96CFAF)");
		}
		if (limit != -1) {
			this.bodyEl.applyStyles("background-size: " + Math.ceil(usage * 100 / limit) + "% 100%; background-repeat: no-repeat");
		}
	};

	var getNextCharge = function()
	{
		if (moduleParams['billing']['ccType'])
			return '$'+moduleParams['billing']['nextAmount']+' on '+moduleParams['billing']['nextAssessmentAt']+' on '+moduleParams['billing']['ccType']+' '+moduleParams['billing']['ccNumber']+' [<a href="#/billing/updateCreditCard">Change card</a>]'
		else
			return '$'+moduleParams['billing']['nextAmount']+' on '+moduleParams['billing']['nextAssessmentAt']+' [<a href="#/billing/updateCreditCard">Set credit card</a>]'
	}
	
	var getEmergSupportStatus = function()
	{
		if (moduleParams['billing']['emergSupport'] == 'included')
			return '<span style="color:green;">Subscribed as part of ' + moduleParams['billing']['productName'] + ' package</span><a href="#" type="" style="display:none;"></a> '+ moduleParams['billing']['emergPhone'];
		else if (moduleParams['billing']['emergSupport'] == "enabled")
			return '<span style="color:green;">Subscribed</span> ($300 / month) [<a href="#" type="unsubscribe">Unsubscribe</a>] '+moduleParams['billing']['emergPhone'];
		else
			return 'Not subscribed [<a href="#" type="subscribe">Subscribe for $300 / month</a>]';
	}

	var getState = function()
	{
		if (moduleParams['billing']['state'] == 'Subscribed')
			return '<span style="color:green;font-weight:bold;">Subscribed</span>';
		else if (moduleParams['billing']['state'] == 'Trial')
			return '<span style="color:green;font-weight:bold;">Trial</span> (<b>' + moduleParams['billing']['trialDaysLeft'] + '</b> days left)';
		else if (moduleParams['billing']['state'] == 'Unsubscribed')
			return '<span style="color:red;font-weight:bold;">Unsubscribed</span> [<a href="#/billing/reactivate">Re-activate</a>]';
		else if (moduleParams['billing']['state'] == 'Behind on payment')
			return '<span style="color:red;font-weight:bold;">Behind on payment</span>'
	}
	
	var couponString = (moduleParams['billing']['couponCode']) ? moduleParams['billing']['couponDiscount'] + ' (Used coupon: ' + moduleParams['billing']['couponCode']+')' : "No discount [<a href='#/billing/applyCouponCode'>enter coupon code</a>]";
	
	var panel = Ext.create('Ext.form.Panel', {
		width: 700,
		title: 'Subscription details',
		bodyPadding: 5,
		bodyCls:'scalr-ui-frame',
		fieldDefaults: {
			anchor: '100%',
			labelWidth: 130
		},
		items: [{
			hidden: (moduleParams['billing']['type'] == 'paypal' || (!moduleParams['billing']['isLegacyPlan'] && moduleParams['billing']['id'])),
			cls: 'scalr-ui-form-field-warning',
			border: false,
			html: "You're under an old plan that doesn't allow for metered billing. If you want to get access to the new features we recently announced, <a href='#/billing/changePlan'>please upgrade your subscription</a>.",
		}, {
			hidden: (moduleParams['billing']['type'] != 'paypal'),
			cls: 'scalr-ui-form-field-warning',
			border: false,
			html: "Hey mate, I see that you are using Paypal for your subscription. Unfortunately paypal hasn't been working too well for us, so we've discontinued its use."+
				  "<br/><a href='#/billing/changePlan'>Click here to switch to direct CC billing</a>, and have your subscription to paypal canceled.",
		}, {
			xtype: 'displayfield',
			hidden: (moduleParams['billing']['type']),
			fieldLabel: 'Plan',
			value: moduleParams['billing']['productName'] + " ( $"+moduleParams['billing']['productPrice']+" / month) [<a href='#/billing/changePlan'>Change plan</a>]"
		}, {
			xtype: 'displayfield',
			hidden: (moduleParams['billing']['type']),
			fieldLabel: 'Status',
			value: getState()
		}, {
			xtype: 'displayfield',
			hidden: (moduleParams['billing']['type']),
			fieldLabel: 'Balance',
			value: "$"+moduleParams['billing']['balance']
		}, {
			xtype: 'displayfield',
			hidden: (moduleParams['billing']['type']),
			fieldLabel: 'Discount',
			value: couponString
		}, {
			xtype: 'fieldset',
			title: 'Account usage',
			hidden: (moduleParams['billing']['type'] == 'paypal' || moduleParams['billing']['isLegacyPlan']),
			defaults: {
				labelWidth: 120
			},
			items: [{
				hidden:true,
				cls: 'scalr-ui-form-field-warning',
				border: false,
				html: "You're using more servers than allowed by your plan. <a href='#/billing/changePlan'>Click here to upgrade your subscription</a>.",
			}, {
				xtype: 'fieldcontainer',
				fieldLabel: 'Servers',
				layout: 'hbox',
				items: [{
					xtype: 'displayfield',
					width: 145,
					limit: moduleParams['limits']['account.servers'],
					listeners: {
						afterrender: applyLimit
					}
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: '[<a href="#/billing/changePlan">Increase limit</a>]'
				}]
			}, {
				xtype: 'fieldcontainer',
				fieldLabel: 'Farms',
				layout: 'hbox',
				items: [{
					xtype: 'displayfield',
					width: 145,
					limit: moduleParams['limits']['account.farms'],
					listeners: {
						afterrender: applyLimit
					}
				}]
			}, {
				xtype: 'fieldcontainer',
				fieldLabel: 'Environments',
				layout: 'hbox',
				items: [{
					xtype: 'displayfield',
					width: 145,
					limit: moduleParams['limits']['account.environments'],
					listeners: {
						afterrender: applyLimit
					}
				}/*, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: '[<a href="#/billing/buyEnvironments">Buy more for $99 / environment / month</a>]'
				}*/]
			}, {
				xtype: 'fieldcontainer',
				fieldLabel: 'User accounts',
				layout: 'hbox',
				items: [{
					xtype: 'displayfield',
					width: 145,
					limit: moduleParams['limits']['account.users'],
					listeners: {
						afterrender: applyLimit
					}
				}]
			}]
		}, {
			xtype: 'fieldset',
			title: 'Features',
			hidden: (moduleParams['billing']['type'] == 'paypal' || moduleParams['billing']['isLegacyPlan']),
			items: [{
				itemId: 'featuresFieldSet',
				xtype: 'displayfield',
				hideLabel: true
			}]
		}, {
			xtype: 'fieldset',
			title: 'Next charge',
			hidden: (moduleParams['billing']['type'] == 'paypal'  || !moduleParams['billing']['id']),
			padding: 10,
			items:[{
				xtype:'component',
				html: getNextCharge()	
			}]
		}, {
			xtype: 'fieldset',
			title: '<a href="http://scalr.net/emergency_support/" target="_blank">Emergency support</a>',
			hidden: (moduleParams['billing']['type'] == 'paypal'  || moduleParams['billing']['isLegacyPlan']),
			padding: 10,
			items:[{
				xtype:'component',
				afterRenderFunc: function(e) {
					this.el.down("a").on('click', function(e){
						e.preventDefault();
					
						var action = this.getAttribute('type');
						
						Scalr.Request({
							confirmBox: {
								type: 'action',
								msg: (action == 'subscribe') ? 'Are you sure want to subscribe to Emergency Support for $300 / month?' : 'Are you sure want to unsubscribe from Emergency Support?',
							},
							processBox: {
								type: 'action'
							},
							params:{action: action},
							url: '/billing/xSetEmergSupport/',
							success: function () {
								moduleParams['billing']['emergSupport'] = (action == 'subscribe') ? 'enabled' : 'disabled';
								panel.down("#emergSupport").update(getEmergSupportStatus());
								panel.down("#emergSupport").afterRenderFunc();
								Scalr.message.Success((action == 'subscribe') ? "You've successfully subscribed to Emergency support" : "You've successfully unsubscribed from emergency support");
							}
						});
					});
				},
				itemId: 'emergSupport',
				html: getEmergSupportStatus(),
				listeners:{
					afterrender: function() {
						this.afterRenderFunc();
					}
				}
			}]
		}, {
			xtype: 'fieldset',
			title: 'Invoices',
			hidden: (moduleParams['billing']['type'] == 'paypal' || !moduleParams['billing']['ccType']),
			padding: 10,
			style: {
				fontSize: '12px'
			},
			items: [{
				xtype: 'button',
				text: 'Compile invoices',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/billing/invoicesList');
				}
			}]
		}]
	});
	
	var featuresText = "";
	for (var name in moduleParams['features']) {
		var isEnabled = moduleParams['features'][name];
		
		if (isEnabled)
			featuresText += "<span style='color:green'>[" + name + "]</span> ";
		else
			featuresText += "<span style='color:gray'>[" + name + "]</span> ";
	}
	
	panel.down("#featuresFieldSet").setValue(featuresText);
	
	return panel;
});
