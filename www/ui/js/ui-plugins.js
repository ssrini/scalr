/*
 * Messages system
 */
Ext.ns('Scalr.message');

Scalr.message.keepMessagesFlag = false;
Scalr.message.Add = function(message, type) {
	if (Ext.isArray(message)) {
		var s = '';
		for (var i = 0; i < message.length; i++)
			'<li>' + message[i] + '</li>'
		message = '<ul>' + s + '</ul>';
	}
	Ext.get('body-container-messages').update(
		'<div class="scalr-ui-message">' +
			'<div class="scalr-ui-message-' + type + '">' + message + '</div><div class="x-tool" style="position: absolute; right: 0; top: 0; margin-top: 5px; margin-right: 5px;"><img src="' + Ext.BLANK_IMAGE_URL + '"></div>' +
		'</div>'
	);
	Ext.get('body-container-messages').down('div.x-tool').addClsOnOver('x-tool-over').on('click', function () {
		this.parent('.scalr-ui-message').destroy();
	});
};

Scalr.message.Error = function(message) {
	Scalr.message.Add(message, 'error');
};

Scalr.message.Success = function(message) {
	Scalr.message.Add(message, 'success');
};

Scalr.message.Info = function(message) {
	Scalr.message.Add(message, 'info');
};

Scalr.message.Warning = function(message) {
	Scalr.message.Add(message, 'warning');
};

Scalr.message.Flush = function () {
	if (! this.keepMessagesFlag) {
		Ext.get('body-container-messages').update();
	}
};

Scalr.message.SetKeepMessages = function (flag) {
	this.keepMessagesFlag = flag;
};

/*
 * Data plugins
 */
Ext.define('Scalr.ui.DataReaderJson', {
	extend: 'Ext.data.reader.Json',
	alias : 'reader.scalr.json',

	type: 'json',
	root: 'data',
	totalProperty: 'total',
	successProperty: 'success'
});

Ext.define('Scalr.ui.DataProxyAjax', {
	extend: 'Ext.data.proxy.Ajax',
	alias: 'proxy.scalr.paging',

	reader: 'scalr.json'
});

Ext.define('Scalr.ui.StoreReaderObject', {
	extend: 'Ext.data.reader.Json',
	alias: 'reader.object',

	readRecords: function (data) {
		var me = this, result = [];

		for (var i in data) {
			if (Ext.isString(data[i]))
				result[result.length] = { id: i, name: data[i] }; // format id => name
			else
				result[result.length] = data[i];
		}

		return me.callParent([result]);
	}
});

Ext.define('Scalr.ui.StoreProxyObject', {
	extend: 'Ext.data.proxy.Memory',
	alias: 'proxy.object',

	reader: 'object',

	/**
	* Reads data from the configured {@link #data} object. Uses the Proxy's {@link #reader}, if present
	* @param {Ext.data.Operation} operation The read Operation
	* @param {Function} callback The callback to call when reading has completed
	* @param {Object} scope The scope to call the callback function in
	*/
	read: function(operation, callback, scope) {
		var me     = this,
			reader = me.getReader();

		if (Ext.isDefined(operation.data))
			me.data = operation.data;

		var result = reader.read(me.data);

		Ext.apply(operation, {
			resultSet: result
		});

		operation.setCompleted();
		operation.setSuccessful();
		Ext.callback(callback, scope || me, [operation]);
	}
});

/*
 * Grid plugins
 */
Ext.define('Scalr.ui.GridStorePlugin', {
	extend: 'Ext.AbstractPlugin',
	alias: 'plugin.gridstore',
	loadMask: false,

	init: function (client) {
		client.getView().loadMask = this.loadMask;
		client.getView().emptyText = '<div class="scalr-ui-grid-empty">' + client.getView().emptyText + '</div>';

		client.store.proxy.view = client.getView(); // :(

		client.store.on({
			scope: client,
			beforeload: function () {
				this.getView().update();
				if (! this.getView().loadMask)
					this.processBox = Scalr.utils.CreateProcessBox({
						type: 'action',
						msg: 'Loading data. Please wait...'
					});
			},
			load: function (store, records, success, operation, options) {
				if (! this.getView().loadMask)
					this.processBox.close();
			}
		});

		client.store.proxy.on({
			exception: function (proxy, response, operation, options) {
				var message = 'Unable to load data';
				try {
					var result = Ext.decode(response.responseText);
					if (result.success === false && result.errorMessage)
						message += ' (' + result.errorMessage + ')';
				} catch (e) {}
				
				message += '. <a href="#">Refresh</a>';

				proxy.view.update('<div class="scalr-ui-grid-error">' + message + '</div>');
				proxy.view.el.down('a').on('click', function (e) {
					e.preventDefault();
					client.store.load();
				});
			}
		});
	}
});

Ext.define('Scalr.ui.SwitchViewPlugin', {
	extend: 'Ext.AbstractPlugin',
	alias: 'plugin.switchview',
	
	init: function (client) {
		client.on('beforerender', function () {
			var field = this.down('[xtype="tbswitchfield"]');
			if (field) {
				this.activeItem = field.switchValue;

				field.on('statesave', function (c, state) {
					this.getLayout().setActiveItem(state.switchValue);
				}, this);
			}
		}, client);
	}
});

Ext.define('Scalr.ui.PagingToolbar', {
	extend: 'Ext.PagingToolbar',
	alias: 'widget.scalrpagingtoolbar',

	pageSizes: [10, 15, 25, 50, 100],
	pageSizeMessage: '{0} items per page',
	pageSizeStorageName: 'grid-ui-page-size',
	autoRefresh: 0,
	autoRefreshTask: 0,

	checkRefreshHandler: function (item, enabled) {
		if (enabled) {
			this.autoRefresh = item.autoRefresh;
			if (this.autoRefresh) {
				clearInterval(this.autoRefreshTask);
				this.autoRefreshTask = setInterval(this.refreshHandler, this.autoRefresh * 1000);
				this.down('#refresh').setIconCls('x-tbar-loading-refresh');
			} else {
				clearInterval(this.autoRefreshTask);
				this.down('#refresh').setIconCls('x-tbar-loading');
			}
		}
	},

	getPagingItems: function () {
		var me = this, items = me.callParent();

		for (var i = 0; i < items.length; i++) {
			if (items[i].itemId == 'refresh') {
				items[i].xtype = 'splitbutton';
				items[i].menu = [{
					text: 'Auto-refresh off',
					checked: true,
					group: 'ScalrPagingToolbarRefresh',
					checkHandler: this.checkRefreshHandler,
					scope: this,
					autoRefresh: 0
				}, {
					text: 'Auto-refresh every 60s',
					checked: false,
					group: 'ScalrPagingToolbarRefresh',
					checkHandler: this.checkRefreshHandler,
					scope: this,
					autoRefresh: 60
				}];
			}
		}

		items.push('-', {
			itemId: 'pageSize',
			text: Ext.String.format(me.pageSizeMessage, 0),
			menu: []
		});

		return items;
	},

	changePageSize: function (item) {
		var me = this;

		Ext.state.Manager.set(me.pageSizeStorageName, item.scalrValue ? 0 : item.pageSize);
		me.items.get('pageSize').setText(Ext.String.format(me.pageSizeMessage, item.pageSize));
		me.up('panel').store.pageSize = item.pageSize;
		me.up('panel').store.loadPage(1);
	},

	tryToGetHeight: function (component) {
		var me = this, panel = this.up('panel'), view = (panel.getLayout().type == 'card') ? panel.getLayout().getActiveItem().view : panel; 

		if (Ext.isDefined(component.height) && view && view.rendered) {
			// try to discover optimal PageSize
			var num = Math.floor(view.el.getHeight() / 24); // row's height

			var fl = true;
			for (var i = 0; i < this.pageSizes.length; i++)
				if (this.pageSizes[i] == num) {
					fl = false;
					break;
				}

			if (fl)
				this.pageSizes.push(num);

			this.pageSizes.sort(function (a, b) {
				if (a < b)
					return -1;
				else if (a > b)
					return 1;
				else
					return 0;
			});

			// replace with saved value
			var s = Ext.state.Manager.get(this.pageSizeStorageName, -1);
			if (s > 0)
				num = s;

			component.store.pageSize = num;
			var c = this.items.get('pageSize');

			for (var i = 0; i < this.pageSizes.length; i++) {
				c.menu.add({
					group: 'pagesize',
					text: this.pageSizes[i].toString(),
					checked: this.pageSizes[i] == num,
					handler: this.changePageSize,
					pageSize: this.pageSizes[i],
					scalrValue: fl && (this.pageSizes[i] == num) ? true : false,
					scope: this
				});
			}
			c.setText(Ext.String.format(me.pageSizeMessage, num));

			if (Ext.isObject(this.data)) {
				component.store.loadData(this.data.data);
				component.store.totalCount = this.data.total;
			} else
				component.store.load();

		} else
			this.up('panel').on('afterlayout', this.tryToGetHeight, this, { single: true });
	},

	initComponent: function () {
		this.callParent();

		this.on('added', function () {
			this.up('panel').on('afterlayout', this.tryToGetHeight, this, { single: true });

			this.refreshHandler = Ext.Function.bind(function () {
				this.up('panel').store.load();
			}, this);

			this.up('panel').on('activate', function () {
				if (this.autoRefresh)
					this.autoRefreshTask = setInterval(this.refreshHandler, this.autoRefresh * 1000);
			}, this);

			this.up('panel').on('deactivate', function () {
				clearInterval(this.autoRefreshTask);
			}, this);

			this.up('panel').store.on('load', function () {
				if (this.autoRefreshTask) {
					clearInterval(this.autoRefreshTask);
					if (this.autoRefresh)
						this.autoRefreshTask = setInterval(this.refreshHandler, this.autoRefresh * 1000);
				}
			}, this);
		});
	}
});

Ext.define('Scalr.ui.GridOptionsColumn', {
	extend: 'Ext.grid.column.Column',
	alias: 'widget.optionscolumn',

	constructor: function () {
		this.callParent(arguments);

		Ext.apply(this, {
			text: '&nbsp;',
			hideable: false,
			width: 116,
			minWidth: 116,
			align: 'center',
			tdCls: 'scalr-ui-grid-options-column'
		});

		this.optionsMenu = Ext.create('Ext.menu.Menu', {
			items: this.optionsMenu,
			listeners: {
				click: function (menu, item, e) {
					if (Ext.isFunction (item.menuHandler)) {
						item.menuHandler(item);
						e.preventDefault();
					} else if (Ext.isObject(item.request)) {
						var r = Scalr.utils.CloneObject(item.request);
						r.params = r.params || {};

						if (Ext.isObject(r.confirmBox))
							r.confirmBox.msg = new Ext.Template(r.confirmBox.msg).applyTemplate(item.record.data);

						if (Ext.isFunction(r.dataHandler)) {
							r.params = Ext.apply(r.params, r.dataHandler(item.record));
							delete r.dataHandler;
						}

						Scalr.Request(r);
						e.preventDefault();
					}
				}
			}
		});

		// hack, fix me (autoShow, autoRender)
		this.optionsMenu.showAt(5000, 5000);
		this.optionsMenu.width = this.optionsMenu.getEl().getWidth();
		this.optionsMenu.hide();
	},

	renderer: function (value, meta, record, rowIndex, colIndex) {
		if (this.headerCt.getHeaderAtIndex(colIndex).getVisibility(record))
			return '<div class="scalr-ui-grid-options-column-btn">Options<div class="scalr-ui-grid-options-column-btn-trigger"></div></div>';
	},

	linkTplsCache: {},

	getVisibility: function (record) {
		return true;
	},

	getOptionVisibility: function (item, record) {
		return true;
	},

	beforeShowOptions: function (record, menu) {

	},

	listeners: {
		afterrender: function () {
			this.up('panel').on('itemclick', function (view, record, item, index, e) {
				var btnEl = Ext.get(e.getTarget('div.scalr-ui-grid-options-column-btn'));
				if (! btnEl)
					return;

				this.beforeShowOptions(record, this.optionsMenu);

				this.optionsMenu.items.each(function (item) {
					var display = this.getOptionVisibility(item, record);
					item.record = record;
					item[display ? "show" : "hide"]();
					if (display && item.href) {
						// Update item link
						if (! this.linkTplsCache[item.id]) {
							this.linkTplsCache[item.id] = new Ext.Template(item.href).compile();
						}
						var tpl = this.linkTplsCache[item.id];
						item.el.child('a').dom.href = tpl.apply(record.data);
					}
				}, this);

				var xy = btnEl.getXY();
				this.optionsMenu.showAt([xy[0] - (this.optionsMenu.width - btnEl.getWidth()), xy[1] + btnEl.getHeight()]);
			}, this);
		}
	}
});

Ext.define('Scalr.ui.GridSelectionModel', {
	alias: 'selection.selectedmodel',
	extend: 'Ext.selection.CheckboxModel',

	injectCheckbox: 'last',
	checkOnly: true,

	constructor: function () {
		this.callParent(arguments);

		this.selectedMenu = Ext.create('Ext.menu.Menu', {
			items: this.selectedMenu,
			listeners: {
				scope: this,
				click: function (menu, item, e) {
					var store = this.store, records = this.selected.items, r = Scalr.utils.CloneObject(Ext.apply({}, item.request));
					r.params = r.params || {};
					r.params = Ext.apply(r.params, r.dataHandler(records));

					if (Ext.isFunction(r.success)) {
						r.success = Ext.Function.createSequence(r.success, function() {
							store.load();
						});
					} else {
						r.success = function () {
							store.load();
						};
					}
					delete r.dataHandler;

					Scalr.Request(r);
				}
			}
		});

		// hack, fix me (autoShow, autoRender)
		this.selectedMenu.showAt(5000, 5000);
		this.selectedMenu.width = this.selectedMenu.getEl().getWidth();
		this.selectedMenu.hide();
	},

	bindComponent: function () {
		this.callParent(arguments);

		this.view.on('refresh', function () {
			this.toggleUiHeader(false);
		}, this);
	},

	getHeaderConfig: function() {
		var c = this.callParent();
		c.width = 45;
		c.minWidth = c.width;
		c.headerId = 'scalrSelectedModelCheckbox';
		c.renderTpl =
			'<div id="{id}-titleContainer" class="' + Ext.baseCSSPrefix + 'column-header-inner scalr-ui-grid-selected-column-btn">' +
				'<span id="{id}-textEl" class="' + Ext.baseCSSPrefix + 'column-header-text">' +
					'{text}' +
				'</span>' +
				'<div class="scalr-ui-grid-selected-column-btn-trigger"></div>' +
			'</div>';

		return c;
	},

	getVisibility: function (record) {
		return true;
	},

	renderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
		metaData.tdCls = Ext.baseCSSPrefix + 'grid-cell-special';
		metaData.style = 'margin-left: 2px';

		if (this.getVisibility(record))
			return '<div class="' + Ext.baseCSSPrefix + 'grid-row-checker">&#160;</div>';
	},

	// don't check unavailable items
	selectAll: function(suppressEvent) {
		var me = this,
		selections = [],
		i = 0,
		len,
		start = me.getSelection().length;

		Ext.each(me.store.getRange(), function (record) {
			if (this.getVisibility(record))
				selections.push(record);
		}, this);

		len = selections.length;

		me.bulkChange = true;
		for (; i < len; i++) {
			me.doSelect(selections[i], true, suppressEvent);
		}
		delete me.bulkChange;
		// fire selection change only if the number of selections differs
		me.maybeFireSelectionChange(me.getSelection().length !== start);
	},

	onSelectChange: function() {
		this.callParent(arguments);

		// check to see if all records are selected
		var me = this, selections = [];
		Ext.each(me.store.getRange(), function (record) {
			if (this.getVisibility(record))
				selections.push(record);
		}, this);

		var hdSelectStatus = this.selected.getCount() === selections.length;
		this.toggleUiHeader(hdSelectStatus);
	},

	onHeaderClick: function(headerCt, header, e) {
		if (e.getTarget('div.scalr-ui-grid-selected-column-btn-trigger')) {
			var btnEl = Ext.get(e.getTarget('div.scalr-ui-grid-selected-column-btn-trigger')), xy = btnEl.getXY();

			if (this.selected.length)
				this.selectedMenu.el.unmask();
			else
				this.selectedMenu.el.mask();

			this.selectedMenu.showAt([xy[0] - (this.selectedMenu.width  - btnEl.getWidth()), xy[1] + btnEl.getHeight()]);
			e.stopEvent();
		} else {
			this.callParent(arguments);
		}
	}
});

/**
 * @class Ext.ux.RowExpander
 * @extends Ext.AbstractPlugin
 * Plugin (ptype = 'rowexpander') that adds the ability to have a Column in a grid which enables
 * a second row body which expands/contracts.  The expand/contract behavior is configurable to react
 * on clicking of the column, double click of the row, and/or hitting enter while a row is selected.
 *
 * @ptype rowexpander
 */
Ext.define('Ext.ux.RowExpander', {
    extend: 'Ext.AbstractPlugin',

    requires: [
        'Ext.grid.feature.RowBody',
        'Ext.grid.feature.RowWrap'
    ],

    alias: 'plugin.rowexpander',

    rowBodyTpl: null,

    /**
     * @cfg {Boolean} expandOnEnter
     * <tt>true</tt> to toggle selected row(s) between expanded/collapsed when the enter
     * key is pressed (defaults to <tt>true</tt>).
     */
    expandOnEnter: true,

    /**
     * @cfg {Boolean} expandOnDblClick
     * <tt>true</tt> to toggle a row between expanded/collapsed when double clicked
     * (defaults to <tt>true</tt>).
     */
    expandOnDblClick: true,

    /**
     * @cfg {Boolean} selectRowOnExpand
     * <tt>true</tt> to select a row when clicking on the expander icon
     * (defaults to <tt>false</tt>).
     */
    selectRowOnExpand: false,

    rowBodyTrSelector: '.x-grid-rowbody-tr',
    rowBodyHiddenCls: 'x-grid-row-body-hidden',
    rowCollapsedCls: 'x-grid-row-collapsed',



    renderer: function(value, metadata, record, rowIdx, colIdx) {
        if (colIdx === 0) {
            metadata.tdCls = 'x-grid-td-expander';
        }
        return '<div class="x-grid-row-expander">&#160;</div>';
    },

    /**
     * @event expandbody
     * <b<Fired through the grid's View</b>
     * @param {HtmlElement} rowNode The &lt;tr> element which owns the expanded row.
     * @param {Ext.data.Model} record The record providing the data.
     * @param {HtmlElement} expandRow The &lt;tr> element containing the expanded data.
     */
    /**
     * @event collapsebody
     * <b<Fired through the grid's View.</b>
     * @param {HtmlElement} rowNode The &lt;tr> element which owns the expanded row.
     * @param {Ext.data.Model} record The record providing the data.
     * @param {HtmlElement} expandRow The &lt;tr> element containing the expanded data.
     */

    constructor: function() {
        this.callParent(arguments);
        var grid = this.getCmp();
        this.recordsExpanded = {};
        // <debug>
        if (!this.rowBodyTpl) {
            Ext.Error.raise("The 'rowBodyTpl' config is required and is not defined.");
        }
        // </debug>
        // TODO: if XTemplate/Template receives a template as an arg, should
        // just return it back!
        var rowBodyTpl = Ext.create('Ext.XTemplate', this.rowBodyTpl),
            features = [{
                ftype: 'rowbody',
                columnId: this.getHeaderId(),
                recordsExpanded: this.recordsExpanded,
                rowBodyHiddenCls: this.rowBodyHiddenCls,
                rowCollapsedCls: this.rowCollapsedCls,
                getAdditionalData: this.getRowBodyFeatureData,
                getRowBodyContents: function(data) {
                    return rowBodyTpl.applyTemplate(data);
                }
            },{
                ftype: 'rowwrap'
            }];

        if (grid.features) {
            grid.features = features.concat(grid.features);
        } else {
            grid.features = features;
        }

        // NOTE: features have to be added before init (before Table.initComponent)
    },

    init: function(grid) {
        this.callParent(arguments);

        // Columns have to be added in init (after columns has been used to create the
        // headerCt). Otherwise, shared column configs get corrupted, e.g., if put in the
        // prototype.
        grid.headerCt.insert(0, this.getHeaderConfig());
        grid.on('render', this.bindView, this, {single: true});
    },

    getHeaderId: function() {
        if (!this.headerId) {
            this.headerId = Ext.id();
        }
        return this.headerId;
    },

    getRowBodyFeatureData: function(data, idx, record, orig) {
        var o = Ext.grid.feature.RowBody.prototype.getAdditionalData.apply(this, arguments),
            id = this.columnId;
        o.rowBodyColspan = o.rowBodyColspan - 1;
        o.rowBody = this.getRowBodyContents(data);
        o.rowCls = this.recordsExpanded[record.internalId] ? '' : this.rowCollapsedCls;
        o.rowBodyCls = this.recordsExpanded[record.internalId] ? '' : this.rowBodyHiddenCls;
        o[id + '-tdAttr'] = ' valign="top" rowspan="2" ';
        if (orig[id+'-tdAttr']) {
            o[id+'-tdAttr'] += orig[id+'-tdAttr'];
        }
        return o;
    },

    bindView: function() {
        var view = this.getCmp().getView(),
            viewEl;

        if (!view.rendered) {
            view.on('render', this.bindView, this, {single: true});
        } else {
            viewEl = view.getEl();
            if (this.expandOnEnter) {
                this.keyNav = Ext.create('Ext.KeyNav', viewEl, {
                    'enter' : this.onEnter,
                    scope: this
                });
            }
            if (this.expandOnDblClick) {
                view.on('itemdblclick', this.onDblClick, this);
            }
            this.view = view;
        }
    },

    onEnter: function(e) {
        var view = this.view,
            ds   = view.store,
            sm   = view.getSelectionModel(),
            sels = sm.getSelection(),
            ln   = sels.length,
            i = 0,
            rowIdx;

        for (; i < ln; i++) {
            rowIdx = ds.indexOf(sels[i]);
            this.toggleRow(rowIdx);
        }
    },

    toggleRow: function(rowIdx) {
        var rowNode = this.view.getNode(rowIdx),
            row = Ext.get(rowNode),
            nextBd = Ext.get(row).down(this.rowBodyTrSelector),
            record = this.view.getRecord(rowNode),
            grid = this.getCmp();

        if (row.hasCls(this.rowCollapsedCls)) {
            row.removeCls(this.rowCollapsedCls);
            nextBd.removeCls(this.rowBodyHiddenCls);
            this.recordsExpanded[record.internalId] = true;
            this.view.fireEvent('expandbody', rowNode, record, nextBd.dom);
        } else {
            row.addCls(this.rowCollapsedCls);
            nextBd.addCls(this.rowBodyHiddenCls);
            this.recordsExpanded[record.internalId] = false;
            this.view.fireEvent('collapsebody', rowNode, record, nextBd.dom);
        }


        // If Grid is auto-heighting itself, then perform a component layhout to accommodate the new height
        if (!grid.isFixedHeight()) {
            grid.doComponentLayout();
        }
        this.view.up('gridpanel').determineScrollbars();
    },

    onDblClick: function(view, cell, rowIdx, cellIndex, e) {

        this.toggleRow(rowIdx);
    },

    getHeaderConfig: function() {
        var me                = this,
            toggleRow         = Ext.Function.bind(me.toggleRow, me),
            selectRowOnExpand = me.selectRowOnExpand;

        return {
            id: this.getHeaderId(),
            width: 24,
            sortable: false,
            resizable: false,
            draggable: false,
            hideable: false,
            menuDisabled: true,
            cls: Ext.baseCSSPrefix + 'grid-header-special',
            renderer: function(value, metadata) {
                metadata.tdCls = Ext.baseCSSPrefix + 'grid-cell-special';

                return '<div class="' + Ext.baseCSSPrefix + 'grid-row-expander">&#160;</div>';
            },
            processEvent: function(type, view, cell, recordIndex, cellIndex, e) {
                if (type == "mousedown" && e.getTarget('.x-grid-row-expander')) {
                    var row = e.getTarget('.x-grid-row');
                    toggleRow(row);
                    return selectRowOnExpand;
                }
            }
        };
    }
});

/*
 * Toolbar fields
 */
Ext.define('Scalr.ui.ToolbarCloudLocation', {
	extend: 'Ext.form.field.ComboBox',
	alias: 'widget.fieldcloudlocation',

	localParamName: 'grid-ui-default-cloud-location',
	fieldLabel: 'Location',
	labelWidth: 50,
	width: 250,
	matchFieldWidth: false,
	listConfig: {
		width: 'auto',
		minWidth: 200
	},
	iconCls: 'no-icon',
	displayField: 'name',
	valueField: 'id',
	editable: false,
	queryMode: 'local',
	setCloudLocation: function () {
		if (this.cloudLocation) {
			this.setValue(this.cloudLocation);
		} else {
			var cloudLocation = Ext.state.Manager.get(this.localParamName);
			if (cloudLocation) {
				var ind = this.store.find('id', cloudLocation);
				if (ind != -1)
					this.setValue(cloudLocation);
				else
					this.setValue(this.store.getAt(0).get('id'));
			} else {
				this.setValue(this.store.getAt(0).get('id'));
			}
		}
		this.gridStore.proxy.extraParams.cloudLocation = this.getValue();		
	},
	listeners: {
		change: function () {
			if (! this.getValue())
				this.setCloudLocation();
		},
		select: function () {
			Ext.state.Manager.set(this.localParamName, this.getValue());
			this.gridStore.proxy.extraParams.cloudLocation = this.getValue();
			this.gridStore.loadPage(1);
		},
		added: function () {
			this.setCloudLocation();
		}
	}
});

Ext.define('Scalr.ui.ToolbarFieldFilter', {
	extend: 'Ext.form.field.Trigger',
	alias: 'widget.tbfilterfield',

	fieldLabel: 'Filter',
	hideLabel: true,
	//labelWidth: 30,
	//width: 180,
	trigger1Cls: 'x-form-clear-trigger',
	trigger2Cls: 'x-form-search-trigger',

	hasSearch: false,
	paramName: 'query',
	prevValue: '',
	emptyText: 'Filter',

	validationEvent: false,
	validateOnBlur: false,

	initComponent: function () {
		if (this.store.proxy.extraParams['query'] != '')
			this.value = this.store.proxy.extraParams['query'];

		this.callParent(arguments);

		this.on('specialkey', function(f, e) {
			if(e.getKey() == e.ENTER){
				e.stopEvent();
				(this.hasSearch && this.getRawValue() == this.prevValue )? this.onTrigger1Click() : this.onTrigger2Click();
			}
		}, this);
	},

	initTrigger: function () {
		var me = this,
			triggerEl = me.triggerEl;

		me.callParent(arguments);

		triggerEl.elements[0].setVisibilityMode(Ext.core.Element.DISPLAY);
		triggerEl.elements[0].hide();
	},

	setValue: function (v) {
		this.callParent(arguments);

		if (v && v.length) {
			this.prevValue = v;
			this.store.proxy.extraParams[this.paramName] = v;
			this.hasSearch = true;
			if (this.rendered) {
				this.triggerEl.elements[0].show();
				this.updateEditState();
			}
		} else {
			this.prevValue = '';
		}
	},

	onTrigger1Click: function() {
		if (this.hasSearch) {
			this.setValue();
			this.store.proxy.extraParams[this.paramName] = '';
			this.store.load();
			this.triggerEl.elements[0].hide();
			this.updateEditState();
			this.hasSearch = false;
		}
	},

	onTrigger2Click : function() {
		var v = this.getRawValue();
		if (v.length < 1){
			this.onTrigger1Click();
			return;
		}
		this.prevValue = v;
		this.store.proxy.extraParams[this.paramName] = v;
		this.store.loadPage(1);
		this.hasSearch = true;
		this.triggerEl.elements[0].show();
		this.updateEditState();
	},

	updateEditState: function () {
		var w = this.bodyEl.getWidth(), tw = this.getTriggerWidth()
		this.inputEl.setWidth(w - tw);
		this.triggerWrap.setWidth(this.getTriggerWidth());

		this.callParent(arguments);
	}
});

Ext.define('Scalr.ui.ToolbarFieldFilterInfo', {
	extend: 'Ext.toolbar.TextItem',
	alias: 'widget.tbfilterinfo',

	updateText: function () {
		var values = [];
		this.prev().menu.items.each(function (item) {
			if (item.fieldLabel) {
				if (item.xtype == 'combo' || item.xtype == 'fieldcloudlocation') {
					var value = item.findRecordByValue(item.getValue()).get(item.displayField);
					if (value)
						values.push(item.fieldLabel + ': ' + value);
				} else {
					if (item.getValue())
						values.push(item.fieldLabel + ': ' + item.getValue());
				}
			}
		});

		this.setText(values.join(', '));
	},

	onRender: function() {
		var me = this;
		me.prev().menu.items.each(function (item) {
			item.on('change', me.updateText, me);
		});

		this.callParent(arguments);
		this.updateText();
	}
});


Ext.define('Scalr.ui.ToolbarFieldTime', {
	extend: 'Ext.toolbar.TextItem',
	alias: 'widget.tbtimefield',

	updateText: function () {
		var cur = new Date(), diff = cur.getTime() - this.systemTime.getTime();
		this.systemTime = cur;
		this.time = Ext.Date.add(this.time, Ext.Date.SECOND, diff / 1000);
		this.setText(Ext.Date.format(this.time, "M j, Y H:i:s"));
		if (! this.isDestroyed)
			Ext.Function.defer(this.updateText, 1000, this);
	},

	onRender: function() {
		this.systemTime = new Date();
		this.time = new Date(this.time);
		this.time = Ext.Date.add(this.time, Ext.Date.SECOND, parseInt(this.timeOffset));
		this.time = Ext.Date.add(this.time, Ext.Date.SECOND, 0 - Ext.Date.format(this.systemTime, 'Z'));

		this.callParent(arguments);
		this.updateText();
	}
});

Ext.define('Scalr.ui.ToolbarFieldSwitch', {
	extend: 'Ext.toolbar.TextItem',
	alias: 'widget.tbswitchfield',

	cls: 'scalr-ui-btn-icon-viewswitch',
	text: '<div class="grid"></div><div class="view"></div>',
	
	getState: function () {
		return {
			switchValue: this.switchValue
		};
	},
	
	changeSwitch: function (value) {
		this.switchValue = value;
		this.onStateChange();
	},
	
	onRender: function () {
		this.callParent(arguments);
		
		if (this.switchValue == 'view')
			this.addCls('scalr-ui-btn-icon-viewswitch-view');
		else
			this.addCls('scalr-ui-btn-icon-viewswitch-grid');
		
		this.el.down('.grid').on('click', function () {
			this.removeCls('scalr-ui-btn-icon-viewswitch-view');
			this.addCls('scalr-ui-btn-icon-viewswitch-grid');
			this.changeSwitch('grid');
		}, this);
		
		this.el.down('.view').on('click', function () {
			this.removeCls('scalr-ui-btn-icon-viewswitch-grid');
			this.addCls('scalr-ui-btn-icon-viewswitch-view');
			this.changeSwitch('view');
		}, this);
	}
	
});

Ext.define('Scalr.ui.CustomButton', {
	alias: 'widget.custombutton',
	extend: 'Ext.Component',

	hidden: false,
	disabled: false,
	pressed: false,
	enableToggle: false,
	maskOnDisable: false,

	childEls: [ 'btnEl' ],

	overCls: 'scalr-ui-btn-custom-over',
	pressedCls: 'scalr-ui-btn-custom-pressed',
	disabledCls: 'scalr-ui-btn-custom-disabled',

	initComponent: function() {
		var me = this;
		me.callParent(arguments);

		me.addEvents('click', 'toggle');

		if (Ext.isString(me.toggleGroup)) {
			me.enableToggle = true;
		}
	},

	onRender: function () {
		var me = this;

		me.callParent(arguments);

		me.mon(me.btnEl, {
			click: me.onClick,
			scope: me
		});

		if (me.pressed)
			me.addCls(me.pressedCls);

		Ext.ButtonToggleManager.register(me);
	},

	onDestroy: function() {
		var me = this;
		if (me.rendered) {
			Ext.ButtonToggleManager.unregister(me);
		}
		me.callParent();
	},

	toggle: function(state, suppressEvent) {
		var me = this;
		state = state === undefined ? !me.pressed : !!state;
		if (state !== me.pressed) {
			if (me.rendered) {
				me[state ? 'addCls': 'removeCls'](me.pressedCls);
			}
			me.pressed = state;
			if (!suppressEvent) {
				me.fireEvent('toggle', me, state);
				Ext.callback(me.toggleHandler, me.scope || me, [me, state]);
			}
		}
		return me;
	},

	onClick: function(e) {
		var me = this;
		if (! me.disabled) {
			me.doToggle();
			me.fireHandler(e);
		}
	},

	fireHandler: function(e){
		var me = this,
		handler = me.handler;

		me.fireEvent('click', me, e);
		if (handler) {
			handler.call(me.scope || me, me, e);
		}
	},

	doToggle: function(){
		var me = this;
		if (me.enableToggle && (me.allowDepress !== false || !me.pressed)) {
			me.toggle();
		}
	}
});

Ext.define('Scalr.ui.HighLightComponent', {
	alias: 'widget.highlight',
	extend: 'Ext.Component',

	initComponent: function () {
		this.html = '<pre><code style="display: inline-block; width: 100%;">' + this.html + '</code></pre>';
		this.callParent(arguments);
	},

	afterRender: function () {
		hljs.highlightBlock(this.el.dom);
	}
});

Ext.define('Scalr.ui.FormFieldFarmRoles', {
	extend: 'Ext.form.FieldSet',
	alias: 'widget.farmroles',

	layout: 'column',
	enableFarmRoleId: true,
	enableServerId: true,
	enableAllValue: true,

	initItems: function () {
		var me = this, columnWidth = 0;

		me.callParent();
		if (me.enableServerId)
			columnWidth = .33;
		else
			columnWidth = .50;

		me.add({
			xtype: 'combo',
			hideLabel: true,
			name: 'farmId',
			store: {
				fields: [ 'id', 'name' ],
				proxy: 'object',
				data: me.params['farms']
			},
			valueField: 'id',
			displayField: 'name',
			emptyText: 'Select a farm',
			columnWidth: columnWidth,
			editable: false,
			value: me.params['farmId'] || '',
			queryMode: 'local',
			listeners: {
				change: function () {
					var meL = this;

					if (! me.enableFarmRoleId)
						return;
					
					Scalr.Request({
						url: '/scripts/getFarmRoles/',
						params: { farmId: meL.getValue(), allValue: me.enableAllValue },
						processBox: {
							type: 'load',
							msg: 'Loading farm roles. Please wait ...'
						},
						success: function (data) {
							var field = meL.up('fieldset').down('[name="farmRoleId"]');
							field.show();
							if (Ext.isObject(data.farmRoles)) {
								field.emptyText = 'Select a role';
								field.reset();
								field.store.load({ data: data.farmRoles });
								field.setValue(0);
								field.enable();
							} else {
								field.store.removeAll();
								field.emptyText = 'No roles';
								field.reset();
								field.disable();
							}
							if (me.enableServerId)
								meL.up('fieldset').down('[name="serverId"]').hide();
						}
					});
				}
			}
		});

		me.add({
			xtype: 'combo',
			hideLabel: true,
			name: 'farmRoleId',
			store: {
				fields: [ 'id', 'name', 'platform', 'role_id' ],
				data: me.params['farmRoles'],
				proxy: 'object'
			},
			valueField: 'id',
			displayField: 'name',
			emptyText: 'Select a role',
			columnWidth: columnWidth,
			margin: {
				left: 5
			},
			editable: false,
			value: me.params['farmRoleId'] || '',
			queryMode: 'local',
			hidden: me.params['farmRoleId'] ? false : true,
			listeners: {
				change: function () {
					var meL = this;

					if (! me.enableServerId)
						return;

					if (! meL.getValue() || meL.getValue() == '0') {
						meL.up('fieldset').down('[name="serverId"]').hide();
						return;
					}

					Scalr.Request({
						url: '/scripts/getServers/',
						params: { farmRoleId: meL.getValue(), allValue: me.enableAllValue },
						processBox: {
							type: 'load',
							msg: 'Loading servers. Please wait ...'
						},
						success: function (data) {
							var field = meL.up('fieldset').down('[name="serverId"]');
							field.show();
							if (Ext.isObject(data.servers)) {
								field.emptyText = 'Select a server';
								field.reset();
								field.store.load({ data: data.servers });
								field.setValue('0');
								field.enable();
							} else {
								field.emptyText = 'No running servers';
								field.reset();
								field.disable();
							}
						}
					});
				}
			}
		});

		if (me.enableServerId)
			me.add({
				xtype: 'combo',
				hideLabel: true,
				name: 'serverId',
				store: {
					fields: [ 'id', 'name' ],
					data: me.params['servers'],
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				emptyText: me.params['farmRoleId'] ? 'No running servers' : 'Select a server',
				disabled: Ext.isArray(me.params['servers']) ? true : false,
				columnWidth: columnWidth,
				margin: {
					left: 5
				},
				editable: false,
				value: me.params['serverId'] || '',
				queryMode: 'local',
				hidden: me.params['farmRoleId'] &&me.params['farmRoleId'] != '0' ? false : true
			});
	},
	
	syncItems: function () {
		if (this.enableFarmRoleId && this.down('[name="farmId"]').getValue()) {
			this.down('[name="farmId"]').fireEvent('change');
		} else
			this.down('[name="farmRoleId"]').hide();
		
		if (! this.enableServerId)
			this.down('[name="serverId"]').hide();
	}
});
