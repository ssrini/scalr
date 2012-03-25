Scalr.regPage('Scalr.ui.dm.applications.create', function (loadParams, moduleParams) {
	var form = new Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 900,
		title: 'Deployments &raquo; Applications &raquo; '+((moduleParams['id']) ? 'Edit' : 'Create'),
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},
        scalrOptions: {
            'modal': true
        },

		items: [{
			xtype: 'fieldset',
			title: 'General information',
			labelWidth: 130,
			items: [{
				xtype: 'textfield',
				name: 'name',
				fieldLabel: 'Name',
				value: (moduleParams['application']) ? moduleParams['application']['name'] : ''
			},{
                xtype: 'container',
                layout: 'hbox',
                items: [{
                    fieldLabel: 'Source',
                    width: 835,
                    xtype: 'combo',
                    allowBlank: false,
                    editable: false,
                    itemId: 'sourceList',
                    store: {
                        fields: [ 'id', 'name' ],
                        proxy: 'object',
                        data: moduleParams.sources
                    },
                    name: 'sourceId',
                    displayField: 'name',
                    valueField: 'id',
                    queryMode: 'local',
                    listeners: {
                        added: function() {
                            if (!moduleParams['application'])
                                this.setValue(this.store.getAt(0).get('id'));
                            else
                                this.setValue(moduleParams['application']['source_id']);
                        }
                    }
                },{
                    xtype: 'button',
                    icon: '/ui/images/icons/add_icon_16x16.png',
                    cls: 'x-btn-icon',
                    tooltip: 'Add new Source',
                    margin: {
                        left: 3
                    },
                    listeners: {
                        click: function() {
                            Scalr.event.fireEvent('redirect','/#/dm/sources/create');
                        }
                    }
                }]
            }]
		}, {
			xtype: 'fieldset',
			itemId: 'scripts',
			title: 'Scripts',
			labelWidth: 130,
			items: [{
				cls: 'scalr-ui-form-field-info',
				html: 'Built in variables: %remote_path%',
				border: false
			}, {
				cls: 'scalr-ui-form-field-warning',
				html: 'First line must contain shebang (#!/path/to/interpreter)',
				border: false
			}, {
				xtype: 'textarea',
				name: 'pre_deploy_script',
				fieldLabel: 'Pre-deploy',
				grow: true,
				growMax: 400,
				value: (moduleParams['application']) ? moduleParams['application']['pre_deploy_script'] : ''
			}, {
				xtype: 'textarea',
				name: 'post_deploy_script',
				fieldLabel: 'Post-deploy',
				grow: true,
				growMax: 400,
				value: (moduleParams['application']) ? moduleParams['application']['post_deploy_script'] : ''
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
						form: this.up('form').getForm(),
						url: '/dm/applications/save/',
						params: loadParams,
						success: function (data) {
                            Scalr.event.fireEvent('update', '/farms/build', data.app, 'create');
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
    Scalr.event.on('update', function (target, source, type) {
        if (type == 'create')
            this.down('#sourceList').store.add(source);
    }, form);
    return form;
});
