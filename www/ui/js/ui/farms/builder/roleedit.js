Ext.define('Scalr.ui.FarmBuilderRoleEdit', {
	extend: 'Ext.panel.Panel',
	alias: 'widget.farmroleedit',

	layout: 'card',
	currentRole: null,

	addRoleDefaultValues: function (record) {
		var settings = record.get('settings');

		this.items.each(function(item) {
			if (item.isEnabled(record))
				Ext.apply(settings, item.getDefaultValues(record));
		});

		record.set('settings', settings);
	},

	setCurrentRole: function (record) {
		this.currentRole = record;
	},

	dockedItems: [{
		dock: 'left',
		width: 200,
		border: false,
		bodyPadding: {
			left: 2,
			right: 2,
			bottom: 2
		},
		bodyCls: 'scalr-ui-frame',
		layout: {
			type: 'vbox',
			align: 'stretch',
			pack: 'start'
		},
		itemId: 'tabs'
	}],

	onAdd: function (cmp) {
		cmp.tabButton = this.getDockedComponent('tabs').add({
			xtype: 'button',
			text: cmp.tabTitle,
			toggleGroup: 'tabs',
			allowDepress: false,
			tabCmp: cmp,
			handler: function (b) {
				this.layout.setActiveItem(b.tabCmp);
			},
			scope: this,
			margin: {
				top: 1
			}
		});
	},

	listeners: {
		beforeactivate: function () {
			var record = this.currentRole, me = this;
			
			if (record.get('is_bundle_running') == true) {
				Scalr.message.Error('This role is locked by server snapshot creation process. Please wait till snapshot will be created.');
				return false;
			}
			else {
				me.items.each(function(item) {
					item.setCurrentRole(record);

					if (item.isEnabled(record)) {
						item.tabButton.show();
					}
					else {
						item.tabButton.hide();
					}
				});

				me.items.each(function(item) {
					if (item.isEnabled(record)) {
						me.layout.setActiveItem(item);
						me.layout.getActiveItem().tabButton.toggle(true);
						return false;
					}
				});
			}
		},

		deactivate: function () {
			if (this.layout.activeItem) {
				this.layout.activeItem.hide();
				this.layout.activeItem.fireEvent('deactivate', this.layout.activeItem);
				this.layout.activeItem = null;
			}
		}
	}
});

