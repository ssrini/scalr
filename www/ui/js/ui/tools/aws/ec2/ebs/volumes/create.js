Scalr.regPage('Scalr.ui.tools.aws.ec2.ebs.volumes.create', function (loadParams, moduleParams) {
	loadParams['size'] = loadParams['size'] || 1;

	return Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 700,
		title: 'Tools &raquo; Amazon Web Services &raquo; EBS &raquo; Volumes &raquo; Create',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},

		items: [{
			xtype: 'fieldset',
			title: 'Placement information',
			labelWidth: 130,
			items: [{
				fieldLabel: 'Cloud location',
				xtype: 'combo',
				allowBlank: false,
				editable: false,
				store: {
					fields: [ 'id', 'name' ],
					data: moduleParams.locations,
					proxy: 'object'
				},
				displayField: 'name',
				valueField: 'id',
				queryMode: 'local',
				name: 'cloudLocation',
				width: 200,
				listeners: {
					change: function (){
						this.next('[name="availabilityZone"]').store.proxy.extraParams.cloudLocation = this.getValue();
						this.next('[name="availabilityZone"]').setValue();
						this.next('[name="availabilityZone"]').store.load();
					},
					render: function () {
						this.setValue(loadParams['cloudLocation'] || this.store.getAt(0).get('id'));
					}
				}
			}, {
				fieldLabel:'Availability zone',
				xtype: 'combo',
				store: {
					fields: [ 'id', 'name' ],
					proxy: {
						type: 'ajax',
						url: '/platforms/ec2/xGetAvailZones',
						reader: {
							type: 'json',
							root: 'data'
						}
					}
				},
				valueField: 'id',
				displayField: 'name',
				editable: false,
				name: 'availabilityZone',
				width: 200
			}]
		}, {
			xtype: 'fieldset',
			title: 'Volume information',
			labelWidth: 130,
			items: [{
				xtype:'fieldcontainer',
				fieldLabel: 'Size',
				layout: 'hbox',
				items:[{
					xtype: 'textfield',
					name: 'size',
					value: loadParams['size'],
					validator: function (value) {
						if (loadParams['snapshotId'] && value < loadParams['size'])
							return "Volume size should be equal or greater than snapshot size";
						else
							return true;
					},
					width: 100
				}, {
					xtype: 'displayfield',
					value: 'GB',
					padding: {
						left: 5
					}
				}]
			}, {
				xtype: 'textfield',
				fieldLabel: 'Snapshot',
				readOnly: true,
				name: 'snapshotId',
				value: loadParams['snapshotId'] || ''
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
				text: 'Create',
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						form: this.up('form').getForm(),
						scope: this,
						url: '/tools/aws/ec2/ebs/volumes/xCreate',
						success: function (data) {
							Scalr.event.fireEvent('redirect',
								'#/tools/aws/ec2/ebs/volumes/' + data.data.volumeId + '/view?cloudLocation=' +
								this.up('form').down('[name="cloudLocation"]').getValue()
							);
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
});
