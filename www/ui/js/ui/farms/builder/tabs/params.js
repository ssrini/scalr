Scalr.regPage('Scalr.ui.farms.builder.tabs.params', function (moduleParams) {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Parameters',
		labelWidth: 200,
		paramsCache: {},

		isEnabled: function (record) {
			//return !record.get('behaviors').match("cf_");
			return true;
		},

		beforeShowTab: function (record, handler) {
			
			/*
			if (! moduleParams.farmId) {
				this.cacheSet([], record.get('role_id'));
			}

			if (this.cacheExist(record.get('role_id')))
				handler();
			else
			*/
				Scalr.Request({
					processBox: {
						type: 'action'
					},
					url: '/roles/xGetRoleParams',
					params: {
						roleId: record.get('role_id'),
						farmId: moduleParams.farmId,
						cloudLocation: record.get('cloud_location')
					},
					success: function(data) {
						this.cacheSet(data.params, record.get('role_id'));
						handler();
					},
					failure: function () {
						this.deactivateTab();
					},
					scope: this
				});
		},

		showTab: function (record) {
			var pars = this.cacheGet(record.get('role_id')), params = record.get('params'), comp = this.down('#params'), obj;
			comp.removeAll();

			// set loaded values
			if (! Ext.isObject(params)) {
				params = {};
				for (var i = 0; i < pars.length; i++)
					params[pars[i]['hash']] = pars[i]['value'];

				record.set('params', params);
			}

			if (pars.length) {
				obj = {};
				for (var i = 0; i < pars.length; i++) {
					obj['name'] = pars[i]['hash'];
					obj['fieldLabel'] = pars[i]['name'];
					obj['allowBlank'] = pars[i]['isrequired'] == 1 ? false : true;
					obj['value'] = params[pars[i]['hash']];

					if (pars[i]['type'] == 'text') {
						obj['xtype'] = 'textfield';
						obj['width'] = 200;
					}

					if (pars[i]['type'] == 'textarea') {
						obj['xtype'] = 'textarea';
						obj['width'] = 600;
						obj['height'] = 300;
					}

					if (pars[i]['type'] == 'boolean') {
						obj['xtype'] = 'checkbox';
						obj['checked'] = params[pars[i]['hash']] == 1 ? true : false;
					}

					comp.add(obj);
				}

			} else {
				comp.add({
					xtype: 'displayfield',
					hideLabel: true,
					value: 'No parameters for this role'
				});
			}
		},

		hideTab: function (record) {
			var params = record.get('params'), comp = this.down('#params');

			comp.items.each(function (item) {
				if (item.xtype == 'textfield' | item.xtype == 'textarea')
					params[item.name] = item.getValue()
				else if (item.xtype == 'checkbox')
					params[item.name] = item.getValue() ? 1 : 0;
			});

			record.set('params', params);
		},

		items: [{
			xtype: 'fieldset',
			itemId: 'params'
		}]
	});
});
