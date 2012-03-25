Scalr.regPage('Scalr.ui.tools.aws.autoSnapshotSettings', function (loadParams, moduleParams) {
	text = 'Tools &raquo; Amazon Web Services &raquo; ';
	if(loadParams['type']=='rds') text += 'RDS &raquo; DB Instances &raquo; ';
	else text += 'EC2 &raquo; EBS &raquo; ';
	form = Ext.create('Ext.form.Panel', {
		title: text + loadParams.objectId + ' &raquo; Autosnapshot Settings',
		bodyCls: 'scalr-ui-frame',
		width: 630,
		stateId: 'grid-tools-aws-autosnapshotsettings',
		bodyPadding: 5,
		items: [{
			xtype: 'fieldset',
			title: 'Enable auto-snapshots',
			checkboxToggle: true,
			checkboxName: 'enabling',
			collapsed: moduleParams.settings.objectid ? false : true,
			items: [{
				xtype: 'hiddenfield',
				name: 'type',
				value: loadParams['type']
			},{
				xtype: 'hiddenfield',
				name: 'cloudLocation',
				value: loadParams['cloudLocation'],
			},{
				xtype: 'hiddenfield',
				name: 'objectId',
				value: loadParams['objectId']
			},{
				xtype: 'container',
				layout: {
					type: 'hbox'
				},
				items: [{
					xtype: 'textfield',
					name: 'period',
					labelWidth: 150,
					fieldLabel: 'Create snapshot every',
					value: moduleParams.settings.period ? moduleParams.settings.period : '24'
				},{
					margin: {
						left: 3,
					},
					xtype: 'displayfield',
					width: 150,
					value: 'hour(s)'
				}]
			},{
				xtype: 'container',
				layout: {
					type: 'hbox'
				},
				items: [{
					xtype: 'textfield',
					name: 'rotate',
					labelWidth: 150,
					fieldLabel: 'Snapshots are rotated',
					value:  moduleParams.settings.rotate ? moduleParams.settings.rotate : '10'
				},{
					margin: {
						left: 3,
					},
					xtype: 'displayfield',
					width: 300,
					value: 'times before being removed. (0 - disable rotating)'
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
				text: 'Save',
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						url: '/tools/aws/xSaveAutoSnapshotSettings',
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