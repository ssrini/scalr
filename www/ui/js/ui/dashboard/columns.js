 Ext.define('Scalr.ui.dashboard.Column', {
    extend: 'Ext.container.Container',
    alias: 'widget.dashboard.column',

	cls: 'scalr-ui-dashboard-container',
    html: '<div class = "remove" style= "height: 50px; width: 100%;"><center><br/><a>Remove column<div class="scalr-menu-icon-delete"></div></a><br/></center></div>',
});

Ext.define('Scalr.ui.dashboard.Billing', {
    extend: 'Ext.form.Panel',
    alias: 'widget.dashboard.billing',

    title: 'Billing',
    bodyCls: 'scalr-ui-frame',
    bodyPadding: 5,
    defaults: {
		anchor: '100%'
    },
    items: [{
        xtype: 'displayfield',
		width: 400,
        fieldLabel: 'Plan',
        itemId: 'billPlan'
    },{
        xtype: 'displayfield',
        fieldLabel: 'Status',
        itemId: 'billState'
    },{
        xtype: 'fieldset',
        title: 'Next charge',
        items: {
            anchor: '100%',
            xtype: 'displayfield',
            itemId: 'billCharge'
        }
    }, {
        xtype: 'fieldset',
        title: '<a href = "http://scalr.net/emergency_support/">Emergency support</a>',
        items: [{
            anchor: '100%',
            xtype: 'displayfield',
            itemId: 'billEmerg'
        }]
    }],

    getNextCharge: function(data)
    {
        if (data['ccType'])
            return '$'+data['nextAmount']+' on '+data['nextAssessmentAt']+' on '+data['ccType']+' '+data['ccNumber']+' [<a href="#/billing/updateCreditCard">Change card</a>]'
        else
            return '$'+data['nextAmount']+' on '+data['nextAssessmentAt']+' [<a href="#/billing/updateCreditCard">Set credit card</a>]'
    },

    getEmergSupportStatus: function(data)
    {
        if (data['emergSupport'] == 'included')
            return '<span style="color:green;">Subscribed as part of ' + data['productName'] + ' package</span><a href="#" type="" style="display:none;"></a> '+ data['emergPhone'];
        else if (data['emergSupport'] == "enabled")
            return '<span style="color:green;">Subscribed</span> ($300 / month) [<a href="#" type="unsubscribe">Unsubscribe</a>] ' + data['emergPhone'];
        else
            return 'Not subscribed [<a href="#" type="subscribe">Subscribe for $300 / month</a>]';
    },

    getAccountState: function(data)
    {
        if (data['state'] == 'Subscribed')
            return '<span style="color:green;font-weight:bold;">Subscribed</span>';
        else if (data['state'] == 'Trial')
            return '<span style="color:green;font-weight:bold;">Trial</span> (<b>' + data['trialDaysLeft'] + '</b> days left)';
        else if (data['state'] == 'Unsubscribed')
            return '<span style="color:red;font-weight:bold;">Unsubscribed</span> [<a href="#/billing/reactivate">Re-activate</a>]';
        else if (data['state'] == 'Behind on payment')
            return '<span style="color:red;font-weight:bold;">Behind on payment</span>'
    },

	listeners: {
		afterrender: function () {
			Scalr.Request({
				processComponent: {
					component: this
				},
				url: '/billing/billingInfo',
				scope: this,
				success: function (data) {
					this.down('#billState').setValue(this.getAccountState(data));
					this.down('#billCharge').setValue(this.getNextCharge(data));
					this.down('#billEmerg').setValue(this.getEmergSupportStatus(data));
					this.down('#billPlan').setValue(data['productName'] + ' ( $' + data['productPrice'] + ' / month ) [<a href = "#/billing/changePlan">Change Plan</a>]');
					this.doLayout();
				}
			});
		}
	}
});

 Ext.define('Scalr.ui.dashboard.Farm', {
	 extend: 'Ext.panel.Panel',
	 alias: 'widget.dashboard.farm',

	 title: 'Farm servers',
	 bodyCls: 'scalr-ui-frame',
	 bodyPadding: 5,
	 defaults: {
		 anchor: '100%'
	 },
	items: [{
		xtype: 'dataview',
		width: '100%',
		store: {
			fields: [ 'behaviors', 'group', 'servCount', 'farmRoleId', 'farmId'],
			proxy: 'object'
		},
		border: true,
		deferEmptyText: false,
		emptyText: '<div class="scalr-ui-dashboard-farms-nocontent">No servers running</div>',
		loadMask: false,
		itemSelector: 'div.scalr-ui-dashboard-farms-servers',
		tpl: new Ext.XTemplate(
			'<div align = center>',
				'<tpl for=".">',
					'<a href="#/servers/view?farmId={farmId}&farmRoleId={farmRoleId}" title="{behaviors}"><div class="scalr-ui-dashboard-farms-servers" style="background-image: url(\'/ui/images/icons/{[this.getLocationIcon(values)]}.png\'); background-repeat: no-repeat;">',
						'<p>&nbsp;{servCount}</p>',
					'</div></a>',
				'</tpl>',
			'</div>',{
				getLocationIcon: function (values) {
					var groups = [ "base", "database", "app", "lb", "cache", "mixed", "utils", "cloudfoundry"];
					var behaviors = [
						"cf_cchm",
						"cf_dea",
						"cf_router",
						"cf_service",
						"mq_rabbitmq",
						"lb_www",
						"app_app",
						"app_tomcat",
						"utils_mysqlproxy",
						"cache_memcached",
						"database_cassandra",
						"database_mysql",
						"database_postgresql",
						"database_redis",
						"database_mongodb"
					];

					//Handle CF all-in-one role
					if (values['behaviors'].match("cf_router") && values['behaviors'].match("cf_cloud_controller") && values['behaviors'].match("cf_health_manager") && values['behaviors'].match("cf_dea"))
						return "behaviors/cloudfoundry_cf_all-in-one";

					//Handle CF CCHM role
					if (values['behaviors'].match("cf_cloud_controller") || values['behaviors'].match("cf_health_manager"))
						return "behaviors/cloudfoundry_cf_cchm";

					var b = (values['behaviors'] || '').split(','), key;
					for (var i = 0, len = b.length; i < len; i++) {
						key = values['group'] + '_' + b[i];
						key2 = b[i];

						for (var k = 0; k < behaviors.length; k++ ) {
							if (behaviors[k] == key || behaviors[k] == key2)
								return 'behaviors/' + key;
						}
					}

					for (var i = 0; i < groups.length; i++ ) {
						if (groups[i] == values['group'])
							return 'groups/' + groups[i];
					}
				}
			}
		)
	}],
	 widgetType: 'local',
	 widgetUpdate: function (content) {
		 this.down('dataview').store.load({data: content['servers']});
		 this.title = 'Farm (' + content['name'] + ') servers';
	 }
 });

 Ext.define('Scalr.ui.dashboard.Announcement', {
	 extend: 'Ext.panel.Panel',
	 alias: 'widget.dashboard.announcement',

	 title: 'Announcement',
	 items: {
		 xtype: 'dataview',
		 store: {
			 fields: ['time','text', 'url'],
			 proxy: 'object'
		 },
		 deferEmptyText: false,
		 emptyText: '<div class="scalr-ui-dashboard-farms-nocontent">No news</div>',
		 loadMask: false,
		 itemSelector: 'div.scalr-ui-dashboard-widgets-div',
		 tpl: new Ext.XTemplate(
			'<tpl for=".">',
				 '<a href="{url}" style="text-decoration: none;"><div class="scalr-ui-dashboard-widgets-div" style="',
			 		'<tpl if="xindex%2==0">background-color: #F3F7FC;</tpl>',
			 		'">',
					 '<div class="scalr-ui-dashboard-widgets-desc">{time}</div>',
					 '<div><span class="scalr-ui-dashboard-widgets-message">{text}</span></div>',
				 '</div></a>',
			'</tpl>'
		 )
	 },
	 widgetType: 'local',
	 widgetUpdate: function (content) {
		 this.down('dataview').store.load({
			 data: content
		 });
	 }
 });

 Ext.define('Scalr.ui.dashboard.LastErrors', {
	 extend: 'Ext.panel.Panel',
	 alias: 'widget.dashboard.lasterrors',

	 title: 'Last errors',
	 items: {
		 xtype: 'dataview',
		 store: {
			 fields: [ 'message', 'time'],
			 proxy: 'object'
		 },
		 autoScroll: true,
		 height: 300,
		 deferEmptyText: false,
		 emptyText: '<div class="scalr-ui-dashboard-farms-nocontent">No errors</div>',
		 loadMask: false,
		 itemSelector: 'div.scalr-ui-dashboard-widgets-div',
		 tpl: new Ext.XTemplate(
			 '<tpl for=".">',
			 	'<div title = "{message}"; class = "scalr-ui-dashboard-widgets-div" style="',
			 		'<tpl if="xindex%2==0">background-color: #F3F7FC;</tpl>',
			 			'">',
			 		'<div class="scalr-ui-dashboard-widgets-desc">{time}</div>',
			 		'<div><span class="scalr-ui-dashboard-widgets-message">{message}</span></div>',
			 	'</div>',
			 '</tpl>'
		 )
	 },
	 widgetType: 'local',
	 widgetUpdate: function (content) {
		 this.down('dataview').store.load({
			 data: content
		 });
	 }
 });

 Ext.define('Scalr.ui.dashboard.UsageLastStat', {
	 extend: 'Ext.panel.Panel',
	 alias: 'widget.dashboard.usagelaststat',

	 title: 'Servers Usage Statistics',
	 //height: 200,
	 items: {
		 xtype: 'dataview',
		 store: {
			 fields: ['farm', 'farm_id', 'current', 'recent'],
			 proxy: 'object' 
		 },
		 autoScroll: true,
		 deferEmptyText: false,
		 emptyText: '<div class="scalr-ui-dashboard-farms-nocontent">No statistic</div>',
		 loadMask: false,
		 itemSelector: 'div.scalr-ui-dashboard-widgets-div',
		 tpl: new Ext.XTemplate(
			 '<div class="scalr-ui-dashboard-widgets-desc" style="width:100%; margin-top: 3px;"><div style = "width:30%; text-align: left;  margin-left: 13px; display: inline-block;">Farm</div> <div style = "width:30%; text-align: center; display: inline-block;">This month</div> <div style = "width:30%; text-align: right; display: inline-block;">Last month</div> </div>',
			 '<tpl for=".">',
			 	'<div title = "{message}"; class = "scalr-ui-dashboard-widgets-div" style="',
				'<tpl if="xindex%2==0">background-color: #F3F7FC;</tpl>',
			 	'">',
			 		'<span class="scalr-ui-dashboard-widgets-message" style = "width: 30%;"><a href="#/farms/{farm_id}/view">{farm}</a></span>',
			 		'<span class="scalr-ui-dashboard-widgets-message" style = "width: 30%; margin-left:0px;  text-align: center; "><tpl if="current"><a href="#/statistics/serversusage?farmId={farm_id}">{current}$</a></tpl><tpl if="!current"><img src="/ui/images/icons/false.png" /></tpl></span>',
		 			'<span class="scalr-ui-dashboard-widgets-message" style = "width: 30%; margin-left:0px; text-align: right; "><tpl if="recent"><a href="#/statistics/serversusage?farmId={farm_id}">{recent}$</a></tpl><tpl if="!recent"><img src="/ui/images/icons/false.png" /></tpl></span>',
			 	'</div>',
			 '</tpl>'
		 )
	 },
	 widgetType: 'local',
	 widgetUpdate: function (content) {
		 this.down('dataview').store.load({
			 data: content
		 });
	 }
 });



 Ext.define('Ext.ui.dashboard.Panel', {
	 extend: 'Ext.panel.Panel',
	 alias: 'widget.dashpanel',

	 cls: 'scalr-ui-dashboard-panel',
	 defaultType: 'dashboard.column',
	 autoScroll: true,
	 bodyCls: 'scalr-ui-frame',
	 bodyPadding: 5,
	 layout: {
		 type : 'column'
	 },

	 initComponent : function() {
		 this.callParent();

		 this.addEvents({
			 validatedrop: true,
			 beforedragover: true,
			 dragover: true,
			 beforedrop: true,
			 drop: true
		 });
		 this.on('drop', this.doLayout, this);
	 },

	 // Set columnWidth, and set first and last column classes to allow exact CSS targeting.
	 beforeLayout: function() {
		 var items = this.layout.getLayoutItems(),
			 len = items.length,
			 i = 0,
			 item;

		 for (; i < len; i++) {
			 item = items[i];
			 item.columnWidth = 1 / len;
		 }
		 return this.callParent(arguments);
	 },

	 // private
	 initEvents : function(){
		 this.callParent();
		 this.dd = Ext.create('Ext.ui.dashboard.DropZone', this, this.dropConfig);
	 },

	 // private
	 beforeDestroy : function() {
		 if (this.dd) {
			 this.dd.unreg();
		 }
		 Ext.ui.dashboard.Panel.superclass.beforeDestroy.call(this);
	 },
	 showEditPanel: function () {
		 this.down('#saveButton').show();
		 this.down('#cancelButton').show();
	 },
	 newCol: function () {
		 this.add({
			 margin: {
				 right: 3
			 }
		 });
	 },

	 newWidget: function(type, params) {
		 return {
			 xtype: type,
			 addTools: this.setTools,
			 layout: 'fit',
			 anchor: '100%',
			 params: params,
			 //frame: true,
			 //closable: true,
			 //collapsible: true,
			// animCollapse: true,
			 draggable: true,
			 margin: {
				 bottom: 5
			 }
		 };
	 },
  	setTools: function() { //function for all moduls
		var me = this.up('dashpanel');
		this.tools.push({
			xtype: 'tool',
			type: 'close',
			handler: function(e, toolEl, closePanel) {
				var p = closePanel.up();
				p.el.animate({
					opacity: 0,
					callback: function(){
						p.fireEvent('close', p);
						p[this.closeAction]();
					},
					scope: p
				});
				me.showEditPanel();
			}
		});
 	}
 });
 Ext.define('Ext.ui.dashboard.DropZone', {
	 extend: 'Ext.dd.DropTarget',

	 constructor: function(dash, cfg) {
		 this.dash = dash;
		 Ext.dd.ScrollManager.register(dash.body);
		 Ext.ui.dashboard.DropZone.superclass.constructor.call(this, dash.body, cfg);
		 dash.body.ddScrollConfig = this.ddScrollConfig;
	 },

	 ddScrollConfig: {
		 vthresh: 50,
		 hthresh: -1,
		 animate: true,
		 increment: 200
	 },

	 createEvent: function(dd, e, data, col, c, pos) {
		 return {
			 dash: this.dash,
			 panel: data.panel,
			 columnIndex: col,
			 column: c,
			 position: pos,
			 data: data,
			 source: dd,
			 rawEvent: e,
			 status: this.dropAllowed
		 };
	 },

	 notifyOver: function(dd, e, data) {
		 var xy = e.getXY(),
			 dash = this.dash,
			 proxy = dd.proxy;

		 // case column widths
		 if (!this.grid) {
			 this.grid = this.getGrid();
		 }
		 // handle case scroll where scrollbars appear during drag
		 var cw = dash.body.dom.clientWidth;
		 if (!this.lastCW) {
			 // set initial client width
			 this.lastCW = cw;
		 } else if (this.lastCW != cw) {
			 // client width has changed, so refresh layout & grid calcs
			 this.lastCW = cw;
			 //dash.doLayout();
			 this.grid = this.getGrid();
		 }

		 // determine column
		 var colIndex = 0,
			 colRight = 0,
			 cols = this.grid.columnX,
			 len = cols.length,
			 cmatch = false;

		 for (len; colIndex < len; colIndex++) {
			 colRight = cols[colIndex].x + cols[colIndex].w;
			 if (xy[0] < colRight) {
				 cmatch = true;
				 break;
			 }
		 }
		 // no match, fix last index
		 if (!cmatch) {
			 colIndex--;
		 }

		 // find insert position
		 var overWidget, pos = 0,
			 h = 0,
			 match = false,
			 overColumn = dash.items.getAt(colIndex),
			 widgets = overColumn.items.items,
			 overSelf = false;

		 len = widgets.length;

		 for (len; pos < len; pos++) {
			 overWidget = widgets[pos];
			 h = overWidget.el.getHeight();
			 if (h === 0) {
				 overSelf = true;
			 } else if ((overWidget.el.getY() + (h / 2)) > xy[1]) {
				 match = true;
				 break;
			 }
		 }

		 pos = (match && overWidget ? pos : overColumn.items.getCount()) + (overSelf ? -1 : 0);
		 var overEvent = this.createEvent(dd, e, data, colIndex, overColumn, pos);

		 if (dash.fireEvent('validatedrop', overEvent) !== false && dash.fireEvent('beforedragover', overEvent) !== false) {

			 // make sure proxy width is fluid in different width columns
			 proxy.getProxy().setWidth('auto');

			 if (overWidget) {
				 proxy.moveProxy(overWidget.el.dom.parentNode, match ? overWidget.el.dom : null);
			 } else {
				 proxy.moveProxy(overColumn.el.dom, null);
			 }

			 this.lastPos = {
				 c: overColumn,
				 col: colIndex,
				 p: overSelf || (match && overWidget) ? pos : false
			 };
			 this.scrollPos = dash.body.getScroll();

			 dash.fireEvent('dragover', overEvent);
			 return overEvent.status;
		 } else {
			 return overEvent.status;
		 }

	 },

	 notifyOut: function() {
		 delete this.grid;
	 },

	 notifyDrop: function(dd, e, data) {
		 delete this.grid;
		 if (!this.lastPos) {
			 return;
		 }
		 var c = this.lastPos.c,
			 col = this.lastPos.col,
			 pos = this.lastPos.p,
			 panel = dd.panel,
			 dropEvent = this.createEvent(dd, e, data, col, c, pos !== false ? pos : c.items.getCount());

		 if (this.dash.fireEvent('validatedrop', dropEvent) !== false && this.dash.fireEvent('beforedrop', dropEvent) !== false) {

			 // make sure panel is visible prior to inserting so that the layout doesn't ignore it
			 panel.el.dom.style.display = '';

			 if (pos !== false) {
				 c.insert(pos, panel);
			 } else {
				 c.add(panel);
			 }

			 dd.proxy.hide();
			 this.dash.fireEvent('drop', dropEvent);

			 // scroll position is lost on drop, fix it
			 var st = this.scrollPos.top;
			 if (st) {
				 var d = this.dash.body.dom;
				 setTimeout(function() {
						 d.scrollTop = st;
					 },
					 10);
			 }

		 }
		 delete this.lastPos;
		 panel.up('dashpanel').showEditPanel();
		 return true;
	 },

	 // internal cache of body and column coords
	 getGrid: function() {
		 var box = this.dash.body.getBox();
		 box.columnX = [];
		 this.dash.items.each(function(c) {
			 box.columnX.push({
				 x: c.el.getX(),
				 w: c.el.getWidth()
			 });
		 });
		 return box;
	 },

	 // unregister the dropzone from ScrollManager
	 unreg: function() {
		 Ext.dd.ScrollManager.unregister(this.dash.body);
		 Ext.ui.dashboard.DropZone.superclass.unreg.call(this);
	 }
 });