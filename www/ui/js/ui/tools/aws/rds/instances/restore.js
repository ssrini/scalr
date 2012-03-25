Scalr.regPage('Scalr.ui.tools.aws.rds.instances.restore', function (loadParams, moduleParams) {
	form = Ext.create('Ext.form.Panel', {
		title: 'Tools &raquo; Amazon Web Services &raquo; RDS &raquo; DB Instances &raquo; Restore',
		bodyCls: 'scalr-ui-frame',
		width: 630,
		stateId: 'grid-tools-aws-rds-instances-restore',
		bodyPadding: 5,
		items: [{
			xtype: 'fieldset',
			title: 'General information',
			name: 'enabling',
			items: [{
				xtype: 'hiddenfield',
				name: 'Snapshot',
				value: loadParams.snapshot
			},{
				labelWidth: 200,
				xtype: 'displayfield',
				fieldLabel: 'Snapshot',
				value: loadParams.snapshot
			},{
				labelWidth: 200,
				xtype: 'textfield',
				name: 'DBInstanceIdentifier',
				fieldLabel: 'Identifier',
				allowBlank: false,
			},{
				labelWidth: 200,
				xtype: 'combo',
				name: 'DBInstanceClass',
				fieldLabel: 'Type',
				store: ['db.m1.small','db.m1.large','db.m1.xlarge','db.m2.2xlarge','db.m2.4xlarge'],
				queryMode: 'local',
				allowBlank: false,
				value: 'db.m1.small',
				editable: false
			},{
				labelWidth: 200,
				xtype: 'textfield',
				name: 'Port',
				fieldLabel: 'Port',
				itemId: 'Port',
				value: '3306',
				allowBlank: false,
			},{
				itemId: 'AvailabilityZone',
				labelWidth: 200,
				xtype: 'combo',
				name: 'AvailabilityZone',
				itemId: 'AvailabilityZone',
				fieldLabel: 'Availability Zone',
				store: {
					fields: ['id', 'name'],
					proxy: 'object',
					data: moduleParams.zones
				},
				queryMode: 'local',
				editable: false,
				valueField: 'id',
				displayField: 'name'
			},{
				labelWidth: 200,
				xtype: 'fieldcontainer',
            	fieldLabel: 'Enable Multi Availability Zones',
            	defaultType: 'checkboxfield',
            	items: [{
                    name: 'MultiAZ',
                    listeners: {
                    	change: function(field, value, oldvalue, eOpts){
                    		if(value) field.up('panel').down('#AvailabilityZone').disable();
                    		else field.up('panel').down('#AvailabilityZone').enable();
                    	}
                    }
                }]
			}]
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
				text: 'Restore',
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						url: '/tools/aws/rds/instances/xRestoreInstance',
						form: form.getForm(),
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
			}]
		}]
	});
	return form;
});