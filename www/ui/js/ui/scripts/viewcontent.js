Scalr.regPage('Scalr.ui.scripts.viewcontent', function (loadParams, moduleParams) {
	var form = Ext.create('Ext.form.Panel', {
		bodyCls: 'scalr-ui-frame',
		bodyStyle: 'overflow: auto !important; background-color: background: #FDF6E3;',
		width: 900,
		scalrOptions: {
			'modal': true
		},
		title: 'Scripts &raquo; View &raquo; ' + moduleParams['script']['name'],

		items: [{
			xtype: 'highlight',
			html: moduleParams['content'][moduleParams['latest']]
		}],
		tools: [{
			type: 'maximize',
			handler: function () {
				Scalr.event.fireEvent('maximize');
			}
		}, {
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}]
	});
    if(moduleParams['revision'].length>1) {
        form.addDocked({
            xtype: 'container',
            dock: 'top',
            cls: 'scalr-ui-frame scalr-mainwindow-docked-toolbar',
            items: {
                xtype: 'combobox',
                itemId: 'comboVers',
                margin: {
                    top: 3
                },
                fieldLabel: 'Revision versions',
                editable: false,
                queryMode: 'local',
                displayField: 'revision',
                store: moduleParams['revision'],
                listeners: {
                    change: function (field, newValue, oldValue) {
                        form.removeAll();
                        form.add({
                            xtype: 'highlight',
                            html: moduleParams['content'][newValue]
                        });
                    }
                }
            }
        });
        form.down('#comboVers').setValue(moduleParams['latest']);
    }
    return form;
});
