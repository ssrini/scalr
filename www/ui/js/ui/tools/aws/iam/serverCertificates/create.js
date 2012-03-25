Scalr.regPage('Scalr.ui.tools.aws.iam.serverCertificates.create', function (loadParams, moduleParams) {
	return Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 900,
		title: 'Server Certificates &raquo; Add',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},

		items: [{
			xtype: 'fieldset',
			title: 'General information',
			labelWidth: 140,
			items: [{
				xtype: 'textfield',
				name: 'name',
				fieldLabel: 'Name'
			},{
				xtype: 'textfield',
				name: 'certificate',
				fieldLabel: 'Certificate',
				inputType: 'file'
			}, {
				xtype: 'textfield',
				name: 'privateKey',
				fieldLabel: 'Private key',
				inputType: 'file'
			}, {
				xtype: 'textfield',
				name: 'certificateChain',
				fieldLabel: 'Certificate chain',
				inputType: 'file'
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
				text: 'Upload',
				width: 80,
				handler: function() {
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						form: this.up('form').getForm(),
						url: '/tools/aws/iam/serverCertificates/xSave',
						success: function () {
							Scalr.event.fireEvent('redirect', '#/tools/aws/iam/serverCertificates/view');
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
