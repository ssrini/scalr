Scalr.regPage('Scalr.ui.roles.info', function (loadParams, moduleParams) {
	var avail = [], lst = moduleParams.info.platformsList;
	for (var i = 0, len = lst.length; i < len; i++) {
		avail += lst[i].name;
		if(lst[i].locations != '')
			avail += ' (' + lst[i].locations + ')';
		avail += '<br>';
	}
		

	var form = Ext.create('Ext.form.Panel', {
		title: 'Role "' + moduleParams['name'] + '" information',
		scalrOptions: {
			'modal': true
		},
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 700,
		tools: [{
			type: 'refresh',
			handler: function () {
				Scalr.event.fireEvent('refresh');
			}
		}, {
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}],

		items: [{
			xtype: 'fieldset',
			title: 'General',
			defaults: {
				labelWidth: 150,
				anchor: '100%'
			},
			items: [{
				xtype: 'displayfield',
				fieldLabel: 'Name',
				name: 'name'
			}, {
				xtype: 'displayfield',
				fieldLabel: 'Description',
				value: moduleParams.info.description ? moduleParams.info.description : '<i>Description not available for this role</i>'
			}]
		}, {
			xtype: 'fieldset',
			title: 'Parameters',
			defaults: {
				labelWidth: 150,
                anchor: '100%',
				xtype: 'displayfield'
			},
			items: [{
				fieldLabel: 'Group',
				name: 'groupName'
			}, {
				fieldLabel: 'Behaviors',
				name: 'behaviorsList'
			}, {
				fieldLabel: 'OS',
				name: 'os'
			}, {
				fieldLabel: 'Architecture',
				name: 'architecture'
			}, {
				fieldLabel: 'Scalr agent',
				value: (moduleParams.info.generation == 1 ? 'ami-scripts' : 'scalarizr') + 
				" ("+(moduleParams.info.szrVersion ? moduleParams.info.szrVersion : 'Unknown version')+")"
			}, {
				fieldLabel: 'Tags',
				name: 'tagsString',
				hidden: moduleParams.info.tagsString == '' ? true : false
			}, {
				fieldLabel: 'Installed software',
				value: moduleParams.info.softwareList ? moduleParams.info.softwareList : '<i>Software list not available for this role</i>'
			}, {
				fieldLabel: 'Available in',
				value: avail
			}]
		}]
	});
	form.getForm().setValues(moduleParams['info']);
	return form;
});
