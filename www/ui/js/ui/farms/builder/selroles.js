Ext.define('Scalr.ui.FarmBuilderSelRoles', {
	extend: 'Ext.view.View',
	alias: 'widget.farmselroles',
/*
		this.el.setStyle('overflow', 'hidden');
		this.el.setStyle('position', 'relative');
		this.el.setStyle('background-color', '#FFF');
		this.el.setHeight(this.height);
*/

	initComponent: function () {
		this.callParent();

		this.addEvents(
			'addrole'
		);
	},

	onRender: function () {
		this.callParent(arguments);

		this.viewEl = this.el.createChild({
			tag: 'div', html: '', cls: 'scalr-ui-farmselroles-blocks scalr-ui-farmselroles-view'
		});

		this.buttonAddEl = this.el.createChild({
			tag: 'div', html: '&nbsp;', cls: 'scalr-ui-farmselroles-blocks scalr-ui-farmselroles-add'
		});

		this.buttonMoveLeftEl = this.el.createChild({
			tag: 'div', html: '<img src="/ui/images/ui/farms/selroles/previous.png">', cls: 'scalr-ui-farmselroles-blocks scalr-ui-farmselroles-left scalr-ui-farmselroles-scroll'
		});

		this.buttonMoveRightEl = this.el.createChild({
			tag: 'div', html: '<img src="/ui/images/ui/farms/selroles/next.png">', cls: 'scalr-ui-farmselroles-blocks scalr-ui-farmselroles-right scalr-ui-farmselroles-scroll'
		});

		this.filterInputEl = this.el.createChild({
			tag: 'div', html: '<input type="text">', cls: 'scalr-ui-farmselroles-blocks scalr-ui-farmselroles-filter-input'
		});
		this.filterInputEl.hide();

		this.filterButtonEl = this.el.createChild({
			tag: 'div', html: '&nbsp;', cls: 'scalr-ui-farmselroles-blocks scalr-ui-farmselroles-filter-button'
		});

		this.el.createChild({
			tag: 'div', html: '&nbsp;',cls: 'scalr-ui-farmselroles-blocks scalr-ui-farmselroles-pad'
		});

		this.buttonAddEl.on('click', function (e) {
			this.fireEvent('addrole');
			e.preventDefault();
		}, this);

		this.viewEl.unselectable();
		this.buttonMoveLeftEl.unselectable();
		this.buttonMoveRightEl.unselectable();


		this.on('refresh', this.fixWidth);
		this.on('resize', this.fixSize);

		this.on('beforeitemclick', function (view, record, item, index, e) {
			if (e.getTarget('a.delete', 10, true)) {
				
				if (record.get('is_bundle_running') == true) {
					Scalr.message.Error('This role is locked by server snapshot creation process. Please wait till snapshot will be created.');
					return false;
				}
				
				Scalr.Confirm({
					type: 'delete',
					msg: 'Delete role "' + record.get('name') + '" from farm?',
					success: function () {
						view.store.remove(record);
					}
				});
				return false;
			}
		});

		this.on('refresh', function () {
			this.viewEl.select('ul li div.short').each(function (el) {
				el.on('mouseover', function (e) {
					var el = e.getTarget('div.short', 10, true).next('div.full');
					if (el)
						el.addCls('full-show');
				});

				el.on('mouseout', function (e) {
					var el = e.getTarget('div.short', 10, true).next('div.full');
					if (el)
						el.removeCls('full-show');
				});
			});
		});

		// disallow to deselect role
		this.on('beforecontainerclick', function () {
			return false;
		});

		this.viewEl.on('click', function (e) {
			if (e.getTarget('a[href="#addrole"]')) {
				e.preventDefault();
				this.fireEvent('addrole');
			}
		}, this);

		this.viewEl.on('mousedown', function(e) {
			this.mouseDrag = true; // drag element's list
			this.mouseCancelClickAfterDrag = false; // cancel click when drag mouse
			this.lastXY = e.getXY();
		}, this);

		this.viewEl.on('mouseup', function(e, t) {
			this.mouseDrag = false;
		}, this);

		this.on('beforeclick', function(e) {
			return !this.mouseCancelClickAfterDrag;
		}, this.dataView);

		this.viewEl.on('mousemove', function(e) {
			var xy = e.getXY();
			if (this.lastXY && (xy[0] != this.lastXY[0] || xy[1] != this.lastXY[1])) {
				this.mouseCancelClickAfterDrag = true;
			}

			if (this.mouseDrag) {
				var xy = e.getXY(), s = this.lastXY;
				this.lastXY = xy;

				var scrollOffset = parseInt(this.viewEl.dom.scrollLeft) || 0;
				this.viewEl.scrollTo('left', scrollOffset + s[0] - xy[0]);
			}
		}, this);

		this.viewEl.on('mousewheel', function(e) {
			var scrollOffset = parseInt(this.viewEl.dom.scrollLeft) || 0;
			this.viewEl.scrollTo('left', scrollOffset - e.getWheelDelta() * 130, true);
			e.preventDefault();
		}, this);

		this.buttonMoveLeftEl.on('click', function() {
			var scrollOffset = parseInt(this.viewEl.dom.scrollLeft) || 0;
			this.viewEl.scrollTo('left', scrollOffset + 130, true);
		}, this);

		this.buttonMoveRightEl.on('click', function() {
			var scrollOffset = parseInt(this.viewEl.dom.scrollLeft) || 0;
			this.viewEl.scrollTo('left', scrollOffset - 130, true);
		}, this);

		this.filterButtonEl.on('click', function() {
			if (this.filterButtonEl.is("div.scalr-ui-farmselroles-filter-button-click")) {
				this.filterInputEl.hide();
				this.filterButtonEl.removeCls("scalr-ui-farmselroles-filter-button-click");
			} else {
				this.filterInputEl.show();
				this.filterButtonEl.addCls("scalr-ui-farmselroles-filter-button-click");
			}
		}, this);

		this.filterInputEl.child("input").on('keyup', function (e, t) {
			t = Ext.get(t);
			var len = t.getValue().length;
			if (!Ext.isDefined(this.prevLength) || len != this.prevLength) {
				var value = t.getValue().toLowerCase();
				this.store.filterBy(function (record) {
					return (record.get('name').toLowerCase().search(value) != -1) ? true : false;
				});
			}
			this.prevLength = len;
		}, this);

		this.fixSize();
	},

	fixSize: function () {
		// set width of View (indent from left and right)
		this.viewEl.setWidth(this.el.getWidth() - 80 - 40); // 80 (left), 40 (right)
		this.fixWidth();
	},

	onAdd: function () {
		this.refresh();
	},

	onUpdate: function () {
		this.refresh()
	},

	clearFilter: function () {
		this.store.clearFilter();
		if (this.rendered)
			this.filterInputEl.child("input").dom.value = '';
	},

	fixWidth: function() {
		if (this.rendered) {
			var el = this.viewEl.child('ul');

			if (this.store.getCount() && el)
				el.setWidth(this.store.getCount() * 130); // width + margin (fix)

			var scrollOffset = parseInt(this.viewEl.dom.scrollLeft) || 0;
			//console.log(1);
			this.viewEl.dom.scrollLeft = scrollOffset; // browser will clean scrollLeft if needed
			//console.log(2);

			if (el && el.getWidth() > this.viewEl.getWidth()) {
				this.buttonMoveLeftEl.removeCls('scalr-ui-farmselroles-scroll-disabled');
				this.buttonMoveRightEl.removeCls('scalr-ui-farmselroles-scroll-disabled');
				this.buttonMoveLeftEl.child('img').dom.src = '/ui/images/ui/farms/selroles/previous.png';
				this.buttonMoveRightEl.child('img').dom.src = '/ui/images/ui/farms/selroles/next.png';
			} else {
				this.buttonMoveLeftEl.addCls('scalr-ui-farmselroles-scroll-disabled');
				this.buttonMoveRightEl.addCls('scalr-ui-farmselroles-scroll-disabled');
				this.buttonMoveLeftEl.child('img').dom.src = '/ui/images/ui/farms/selroles/previous_disable.png';
				this.buttonMoveRightEl.child('img').dom.src = '/ui/images/ui/farms/selroles/next_disable.png';
			}
		}
	},

	getTargetEl: function () {
		return this.viewEl;
	}

});

/*Ext.ns("Scalr.Viewers");
	Ext.apply(this.dataView, { refresh: this.dataView.refresh.createSequence(function() {
		this.dataView.fixWidth(this);
	}, this) });

	Ext.apply(this.dataView, { updateIndexes: this.dataView.updateIndexes.createSequence(function(startIndex, endIndex) {
		this.dataView.createLinks(this, startIndex, endIndex);
	}, this) });

	Ext.apply(this.dataView, { onRemove: this.dataView.refresh });

	this.dataView.getStore().on('remove', this.dataView.refresh, this.dataView);
	//this.dataView.getStore().on('save', this.dataView.createLinks.createCallback(this), this.dataView);
	//this.dataView.getStore().un('remove', this.dataView.onRemove);
	//this.dataView.getStore().on('remove', this.dataView.fixWidth, this);
*/
