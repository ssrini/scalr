Scalr.regPage('Scalr.ui.farms.builder.tabs.deployments', function () {
    function addApp (target, app, type) {
        if (type == 'create') {
            this.down('#appList').store.add(app);
            var appList = this.cacheGet(['dmApplications']);
            appList[appList.length] = app;
            this.cacheSet(appList, ['dmApplications']);
        }
    }
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Deployments',

		isEnabled: function (record) {
			return record.get('platform') != 'rds';
		},

		getDefaultValues: function (record) {
			return {
				'dm.remote_path': '/var/www'
			};
		},

		beforeShowTab: function (record, handler) {
			if (this.cacheExist(['dmApplications']))
				handler();
			else
				Scalr.Request({
					processBox: {
						type: 'action'
					},
					url: '/dm/applications/xGetApplications/',
					scope: this,
					success: function (response) {
						this.cacheSet(response.data, ['dmApplications']);
						handler();
					},
					failure: function () {
						this.deactivateTab();
					}
				});
            Scalr.event.on('update', addApp, this);
		},


		showTab: function (record) {
			var settings = record.get('settings');
			this.down('[name="dm.application_id"]').store.load({ data: this.cacheGet(['dmApplications']) });
            if(this.down('[name="dm.application_id"]').store.data.length && this.down('[name="dm.application_id"]').store.getAt(0)['id'] != 0)
                this.down('[name="dm.application_id"]').store.insert(0, {id: 0, name: ''});
			
			this.down('[name="dm.application_id"]').setValue(settings['dm.application_id']);
			this.down('[name="dm.remote_path"]').setValue(settings['dm.remote_path']);
		},

		hideTab: function (record) {
            Scalr.event.un('update', addApp, this);
			var settings = record.get('settings');
			settings['dm.application_id'] = this.down('[name="dm.application_id"]').getValue();
			settings['dm.remote_path'] = this.down('[name="dm.remote_path"]').getValue();
			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			title: 'Deployment options',
			itemId: 'options',
			labelWidth: 150,
			items: [{
                xtype: 'container',
                layout: 'hbox',
                items: [{
                    fieldLabel: 'Application',
                    itemId: 'appList',
                    xtype: 'combo',
                    store: {
                        fields: [ 'id', 'name' ],
                        proxy: 'object'
                    },
                    valueField: 'id',
                    displayField: 'name',
                    editable: false,
                    width: 400,
                    queryMode: 'local',
                    name: 'dm.application_id'
                }, {
                    xtype: 'button',
                    icon: '/ui/images/icons/add_icon_16x16.png',
                    cls: 'x-btn-icon',
                    tooltip: 'Add new Application',
                    margin: {
                        left: 3
                    },
                    listeners: {
                        click: function() {
                            Scalr.event.fireEvent('redirect','/#/dm/applications/create');
                        }
                    }
                }]
            }, {
                xtype:'textfield',
                itemId: 'remotePath',
                width:400,
                fieldLabel: 'Remote path',
                name: 'dm.remote_path'
            }]
		}]
	});
});
