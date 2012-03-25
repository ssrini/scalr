Scalr.regPage('Scalr.ui.billing.invoices', function (loadParams, moduleParams) {
	
	var form = Ext.create('Ext.form.Panel', {
		bodyCls: 'scalr-ui-frame',
		width: 500,
		layout: 'card',
		title: 'Billing &raquo; Invoices',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},
		scalrOptions: {
			'modal': true
		},
		items: [{
			xtype: 'grid',
			itemId: 'view',
			border: false,
			store: {
				proxy: 'object',
				fields: ['createdAt', 'id', 'text']
			},
			plugins: {
				ptype: 'gridstore'
			},

			viewConfig: {
				emptyText: 'No invoices available for your subscription',
				deferEmptyText: false
			},

			columns: [
				{ header: '', flex: 200, sortable: true, dataIndex: '', xtype: 'templatecolumn',
					tpl: '<a href="/billing/{id}/showInvoice" target="_blank">{createdAt}</a>' 
				},
			]				
		}],
		tools: [{
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}]
	});

	form.down('#view').store.load({ data: moduleParams.invoices });

	return form;
});
