if (Scalr.version('4.0')) {
	// fix for dynamic added column SelectedModel (it doesn't have saved state and it's last in list)
	Ext.override(Ext.grid.Panel, {
		getState: function () {
			var state = this.callOverridden();

			if (Ext.isArray(state.columns) && state.columns[state.columns.length - 1].id == 'scalrSelectedModelCheckbox') {
				state.columns.pop();
			}

			if (this.store.sorters.getCount()) {
				state.sort = [];
				this.store.sorters.each(function () {
					state.sort.push({
						property: this.property,
						direction: this.direction
					});
				});
			}

			return state;
		},
		applyState: function (state) {
			var sorter = state.sort;
			delete state.sort;

			this.callOverridden(arguments);

			if (sorter)
				this.store.sort(sorter, '', false);
		}
	});
}

if (Scalr.version('4.0')) {
	// remove unselectable
	Ext.override(Ext.view.Table, {
		afterRender: function() {
			var me = this;

			me.callParent();
			me.mon(me.el, {
				scroll: me.fireBodyScroll,
				scope: me
			});
			me.attachEventsForFeatures();
		}
	});

	// remove unselectable
	Ext.view.TableChunker.metaRowTpl = [
		'<tr class="' + Ext.baseCSSPrefix + 'grid-row {addlSelector} {[this.embedRowCls()]}" {[this.embedRowAttr()]}>',
		'<tpl for="columns">',
		'<td class="{cls} ' + Ext.baseCSSPrefix + 'grid-cell ' + Ext.baseCSSPrefix + 'grid-cell-{columnId} {{id}-modified} {{id}-tdCls} {[this.firstOrLastCls(xindex, xcount)]}" {{id}-tdAttr}><div class="' + Ext.baseCSSPrefix + 'grid-cell-inner ' + Ext.baseCSSPrefix + '" style="{{id}-style}; text-align: {align};">{{id}}</div></td>',
		'</tpl>',
		'</tr>'
	];
}

// show file name in File Field
Ext.override(Ext.form.field.File, {
	setValue: function(value) {
		Ext.form.field.File.superclass.setValue.call(this, value);
	}
});

// submit form on enter on any fields in form
Ext.override(Ext.form.field.Base, {
	initComponent: function () {
		this.callOverridden();

		this.on('specialkey', function (field, e) {
			if (e.getKey() == e.ENTER) {
				var form = field.up('form');
				if (form) {
					var button = form.down('#buttonSubmit');
					if (button) {
						button.handler();
					}
				}
			}
		});
	}
});

Ext.override(Ext.form.field.Checkbox, {
	setReadOnly: function(readOnly) {
		var me = this,
			inputEl = me.inputEl;
		if (inputEl) {
			// Set the button to disabled when readonly
			inputEl.dom.disabled = readOnly || me.disabled;
		}
		me[readOnly ? 'addCls' : 'removeCls'](me.readOnlyCls);
		me.readOnly = readOnly;
	}
});

Ext.override(Ext.form.field.Trigger, {
	updateEditState: function () {
		var me = this;

		me.callOverridden();
		me[me.readOnly ? 'addCls' : 'removeCls'](me.readOnlyCls);
	}
});

//scroll to error fields
Ext.form.Basic.override({
	initialize: function () {
		this.callOverridden();
		this.on('actionfailed', function (basicForm) {
			basicForm.getFields().each(function (field) {
				if (field.getActiveError()) {
					field.el.scrollIntoView(basicForm.owner.body);
					return false;
				}
			});
		});
	}
});

if (Scalr.version('4.0')) {
	/* Error in new version, source code from 4.0.2a (check after 4.0.6) */
	Ext.override(Ext.panel.Table, {
		determineScrollbars: function() {
			var me = this,
				view = me.view,
				box,
				tableEl,
				scrollWidth,
				clientWidth,
				scrollHeight,
				clientHeight,
				verticalScroller = me.verticalScroller,
				horizontalScroller = me.horizontalScroller,
				curScrollbars = (verticalScroller   && verticalScroller.ownerCt === me ? 1 : 0) |
					(horizontalScroller && horizontalScroller.ownerCt === me ? 2 : 0),
				reqScrollbars = 0; // 1 = vertical, 2 = horizontal, 3 = both

			// If we are not collapsed, and the view has been rendered AND filled, then we can determine scrollbars
			if (!me.collapsed && view && view.viewReady && !me.changingScrollBars) {

				// Calculate maximum, *scrollbarless* space which the view has available.
				// It will be the Fit Layout's calculated size, plus the widths of any currently shown scrollbars
				box = view.el.getSize();

				clientWidth  = box.width  + ((curScrollbars & 1) ? verticalScroller.width : 0);
				clientHeight = box.height + ((curScrollbars & 2) ? horizontalScroller.height : 0);

				// Calculate the width of the scrolling block
				// There will never be a horizontal scrollbar if all columns are flexed.

				scrollWidth = (me.headerCt.query('[flex]').length && !me.headerCt.layout.tooNarrow) ? 0 : me.headerCt.getFullWidth();

				// Calculate the height of the scrolling block
				if (verticalScroller && verticalScroller.el) {
					scrollHeight = verticalScroller.getSizeCalculation().height;
				} else {
					tableEl = view.el.child('table', true);
					scrollHeight = tableEl ? tableEl.offsetHeight : 0;
				}

				// View is too high.
				// Definitely need a vertical scrollbar
				if (scrollHeight > clientHeight) {
					reqScrollbars = 1;

					// But if scrollable block width goes into the zone required by the vertical scrollbar, we'll also need a horizontal
					if (horizontalScroller && ((clientWidth - scrollWidth) < verticalScroller.width)) {
						reqScrollbars = 3;
					}
				}

				// View height fits. But we stil may need a horizontal scrollbar, and this might necessitate a vertical one.
				else {
					// View is too wide.
					// Definitely need a horizontal scrollbar
					if (scrollWidth > clientWidth) {
						reqScrollbars = 2;

						// But if scrollable block height goes into the zone required by the horizontal scrollbar, we'll also need a vertical
						if (verticalScroller && ((clientHeight - scrollHeight) < horizontalScroller.height)) {
							reqScrollbars = 3;
						}
					}
				}

				// If scrollbar requirements have changed, change 'em...
				if (reqScrollbars !== curScrollbars) {

					// Suspend component layout while we add/remove the docked scrollers
					me.suspendLayout = true;
					if (reqScrollbars & 1) {
						me.showVerticalScroller();
					} else {
						me.hideVerticalScroller();
					}
					if (reqScrollbars & 2) {
						me.showHorizontalScroller();
					} else {
						me.hideHorizontalScroller();
					}
					me.suspendLayout = false;
				}
				// Lay out the Component.
				// Set a flag so that afterComponentLayout does not recurse back into here.
				me.changingScrollBars = true;
				me.doComponentLayout();
				me.changingScrollBars = false;
			}
		}
	});
}
