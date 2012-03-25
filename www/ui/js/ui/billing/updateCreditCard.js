Scalr.regPage('Scalr.ui.billing.updateCreditCard', function (loadParams, moduleParams) {
	
	var form = Ext.create('Ext.form.Panel', {
		bodyCls: 'scalr-ui-frame',
		width: 500,
		bodyPadding: 5,
		title: 'Billing &raquo; Update CreditCard Information',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},
		scalrOptions: {
			'modal': true
		},
		items: [{
			cls: 'scalr-ui-form-field-info',
			html: 'Your card will be pre-authorized for $1. <a href="http://en.wikipedia.org/wiki/Authorization_hold">What does this mean?</a>',
			border: false
		},{
			xtype: 'fieldcontainer',
			fieldLabel: 'Card number',
			heigth: 24,
			labelWidth: 80,
			layout: 'hbox',
			items: [{
				xtype: 'textfield',
				name: 'ccNumber',
				emptyText: moduleParams['billing']['ccNumber'],
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
			}
			]
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
				text: 'Update',
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						url: '/billing/xUpdateCreditCard/',
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
