Ext.define('Scalr.ui.FarmsBuilderTab', {
	extend: 'Ext.panel.Panel',

	tabTitle: '',
	border: false,
	autoScroll: true,
	bodyCls: 'scalr-ui-frame',
	bodyPadding: 5,
	bodyStyle: {
		'overflow-x': 'hidden'
	},
	currentRole: null,
	cache: {},
	tab: 'tab',

	setCurrentRole: function (record) {
		this.currentRole = record;
	},

	listeners: {
		activate: function () {
			var handler = Ext.Function.bind(this.showTab, this, [this.currentRole]);
			this.beforeShowTab(this.currentRole, handler);
		},
		deactivate: function () {
			this.hideTab(this.currentRole);
		}
	},

	cacheExist: function (args) {
		return Ext.isDefined(this.cache[Ext.isArray(args) ? args.join(' ') : args]);
	},

	cacheGet: function (args) {
		return this.cache[Ext.isArray(args) ? args.join(' ') : args];
	},

	cacheSet: function (value, args) {
		this.cache[Ext.isArray(args) ? args.join(' ') : args] = value;
	},

	beforeShowTab: function (record, handler) {
		this.el.unmask();
		handler();
	},

	// show tab
	showTab: function (record) {},

	// hide tab
	hideTab: function (record) {},

	deactivateTab: function () {
		this.el.mask();
	},

	// tab can show or used for this role
	isEnabled: function (record) {
		return true;
	},

	// default values for new role
	getDefaultValues: function (record) {
		return {};
	}
});
