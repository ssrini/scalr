Scalr.regPage('Scalr.ui.billing.changePlan', function (loadParams, moduleParams) {
	var pkg = moduleParams['package'];

	var setPackage = function(item) {
		if (item.package != 'cancel' && !moduleParams['subscriptionId']) {
			form.down('#ccInfo').show();
		} else {
			form.down('#ccInfo').hide();
		}

		pkg = item.package;
	}

	var form = Ext.create('Ext.form.Panel', {
		tools: [{
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}],
		scalrOptions: {
			'modal': false
		},
		bodyCls: 'scalr-ui-frame',
		bodyPadding: 10,
		width: !moduleParams['subscriptionId'] ? 780 : 900,
		title: 'Billing &raquo; Change Pricing Plan',

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
						url: '/billing/xChangePlan/',
						params: {
							'package': pkg
						},
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

	form.add({
		xtype: 'container',
		layout: {
			type: 'hbox'
		},
		padding: {
			left: 25,
			right: 25,
			top: 8,
			bottom: 8
		},
		defaults: {
			xtype: 'custombutton',
			width: 138,
			height: 158,
			handler: setPackage,
			allowDepress: false,
			toggleGroup: 'scalr-ui-billing-changePlan',
			renderTpl:
				'<div class="{prefix}-wrap">' +
					'<div class="{prefix}-wrap-title"><span>Current</span></div>' +
					'<div class="scalr-ui-btn-custom" id="{id}-btnEl">' +
						'<div class="{prefix}-btn-name">{name}</div>' +
						'<div class="{prefix}-btn-icon"><img src="/ui/images/ui/billing/plans/{icon}"></div>' +
						'<div class="{prefix}-btn-price">{price}</div>' +
					'</div>' +
				'</div>'
		},
		items: [{
			cls: moduleParams['currentPackage'] == 'up-to-80-servers' ? 'scalr-ui-billing-changePlan-current': '',
			pressed: moduleParams['currentPackage'] == 'up-to-80-servers' ? true : false,
			//disabled: moduleParams['availablePackages']['up-to-80-servers'] == 0 ? true : false,
			package: 'up-to-80-servers',
			renderData: {
				icon: 'monopoly.png',
				name: 'Monopoly',
				price: '$2,399',
				prefix: 'scalr-ui-billing-changePlan'
			}
		}, {
			cls: moduleParams['currentPackage'] == 'up-to-40-servers' ? 'scalr-ui-billing-changePlan-current': '',
			pressed: moduleParams['currentPackage'] == 'up-to-40-servers' ? true : false,
			disabled: moduleParams['availablePackages']['up-to-40-servers'] == 0 ? true : false,
			package: 'up-to-40-servers',
			renderData: {
				icon: 'ipo.png',
				name: 'IPO',
				price: '$999',
				prefix: 'scalr-ui-billing-changePlan'
			}
		}, {
			cls: moduleParams['currentPackage'] == 'up-to-20-servers' ? 'scalr-ui-billing-changePlan-current': '',
			pressed: moduleParams['currentPackage'] == 'up-to-20-servers' ? true : false,
			disabled: moduleParams['availablePackages']['up-to-20-servers'] == 0 ? true : false,
			package: 'up-to-20-servers',
			renderData: {
				icon: 'vc.png',
				name: 'VC',
				price: '$399',
				prefix: 'scalr-ui-billing-changePlan'
			}
		}, {
			cls: moduleParams['currentPackage'] == 'up-to-10-servers' ? 'scalr-ui-billing-changePlan-current': '',
			pressed: moduleParams['currentPackage'] == 'up-to-10-servers' ? true : false,
			disabled: moduleParams['availablePackages']['up-to-10-servers'] == 0 ? true : false,
			package: 'up-to-10-servers',
			renderData: {
				icon: 'angel.png',
				name: 'Angel',
				price: '$199',
				prefix: 'scalr-ui-billing-changePlan'
			}
		}, {
			cls: moduleParams['currentPackage'] == 'up-to-5-servers' ? 'scalr-ui-billing-changePlan-current': '',
			pressed: moduleParams['currentPackage'] == 'up-to-5-servers' ? true : false,
			disabled: moduleParams['availablePackages']['up-to-5-servers'] == 0 ? true : false,
			package: 'up-to-5-servers',
			renderData: {
				icon: 'seed.png',
				name: 'Seed',
				price: '$99',
				prefix: 'scalr-ui-billing-changePlan'
			}
		}, {
			package: 'cancel',
			pressed: moduleParams['currentPackage'] == 'cancel' ? true : false,
			hidden: !moduleParams['subscriptionId'] ? true : false,
			renderData: {
				icon: 'stop_sign.png',
				name: 'Unsubscribe',
				prefix: 'scalr-ui-billing-changePlan'
			}
		}]
	});

	form.add({
		xtype: 'fieldset',
		itemId: 'ccInfo',
		hidden: true,
		title: 'Credit card information',
		padding: {
			left: 15,
			right: 15,
			top: 15,
			bottom: 15
		},
		items:[{
			cls: 'scalr-ui-form-field-info',
			html: 'Your card will be pre-authorized for $1. <a href="http://en.wikipedia.org/wiki/Authorization_hold" target="_blank">What does this mean?</a>',
			border: false
		}, {
			xtype: 'fieldcontainer',
			fieldLabel: 'Card number',
			heigth: 24,
			labelWidth: 80,
			layout: 'hbox',
			items: [{
				xtype: 'textfield',
				name: 'ccNumber',
				emptyText: '',
				height: 23,
				value: ''
			},
			{ xtype: 'component', height: 23, width: 37, margin: { left: 5 }, html: '<img src="/ui/images/ui/billing/cc_visa.png" />'},
			{ xtype: 'component', height: 23, width: 37, margin: { left: 5 }, html: '<img src="/ui/images/ui/billing/cc_mc.png" />'},
			{ xtype: 'component', height: 23, width: 37, margin: { left: 5 }, html: '<img src="/ui/images/ui/billing/cc_amex.png" />'},
			{ xtype: 'component', height: 23, width: 37, margin: { left: 5 }, html: '<img src="/ui/images/ui/billing/cc_discover.png" />'}
			]
		}, {
			xtype: 'fieldcontainer',
			fieldLabel: 'CVV code',
			heigth: 24,
			labelWidth: 80,
			layout: 'hbox',
			items: [{
				xtype: 'textfield',
				name: 'ccCvv',
				height: 23,
				width: 40,
				value: ''
			},
			{ xtype: 'displayfield', value:'Exp. date:', margin: { left:10 }},
			{ 
				xtype: 'combo',
				name: 'ccExpMonth',
				margin: { left:5 },
				hideLabel: true,
				editable: false,
				value:'01',
				store: {
					fields: [ 'name', 'description' ],
					proxy: 'object',
					data:[
						{name:'01', description:'01 - January'},
						{name:'02', description:'02 - February'},
						{name:'03', description:'03 - March'},
						{name:'04', description:'04 - April'},
						{name:'05', description:'05 - May'},
						{name:'06', description:'06 - June'},
						{name:'07', description:'07 - July'},
						{name:'08', description:'08 - August'},
						{name:'09', description:'09 - September'},
						{name:'10', description:'10 - October'},
						{name:'11', description:'11 - November'},
						{name:'12', description:'12 - December'}
					]
				},
				valueField: 'name',
				displayField: 'description',
				queryMode: 'local'
			}, { 
				xtype: 'combo',
				name: 'ccExpYear',
				margin: { left:5 },
				width: 65,
				value:'2012',
				hideLabel: true,
				editable: false,
				store: {
					fields: [ 'name', 'description' ],
					proxy: 'object',
					data:[
						{name:'2011', description:'2011'},
						{name:'2012', description:'2012'},
						{name:'2013', description:'2013'},
						{name:'2014', description:'2014'},
						{name:'2015', description:'2015'},
						{name:'2016', description:'2016'},
						{name:'2017', description:'2017'},
						{name:'2018', description:'2018'},
						{name:'2019', description:'2019'},
						{name:'2020', description:'2020'},
						{name:'2021', description:'2021'},
						{name:'2022', description:'2022'}
					]
				},
				valueField: 'name',
				displayField: 'description',
				queryMode: 'local'
			}]
		}, {
			xtype: 'textfield',
			labelWidth: 80,
			name:'firstName',
			fieldLabel: 'First name',
			value: moduleParams['firstName']
		}, {
			xtype: 'textfield',
			labelWidth: 80,
			name:'lastName',
			fieldLabel: 'Last name',
			value: moduleParams['lastName']
		}, {
			xtype: 'textfield',
			labelWidth: 80,
			name:'postalCode',
			fieldLabel: 'Postal code',
			value: ''	
		}]
	});

	return form;
});
