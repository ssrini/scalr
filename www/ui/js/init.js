// catch server error page (404, 403, timeOut and other)
Ext.Ajax.on('requestexception', function(conn, response, options) {
	if (options.doNotShowError == true)
		return;

	if (response.status == 403) {
		var f = Scalr.application.MainWindow.getComponent('loginForm');
		f.setTitle('Session expired. Please login again');
		Scalr.application.MainWindow.layout.setActiveItem(f);
		f.header.el.down('span').applyStyles('color: #D50000');
	} else if (response.status == 404) {
		Scalr.message.Error('Page not found.');
	} else if (response.timedout == true) {
		Scalr.message.Error('Server didn\'t respond in time. Please try again in a few minutes.');
	} else if (response.aborted == true) {
		Scalr.message.Error('Request was aborted by user.');
	} else {
		if (Scalr.timeoutHandler.enabled) {
			Scalr.timeoutHandler.undoSchedule();
			Scalr.timeoutHandler.run();
		}
		Scalr.message.Error('Cannot proceed your request at the moment. Please try again later.');
	}
});

Scalr.event = new Ext.util.Observable();
Scalr.EventMessager = Scalr.event; // for old code
/*
 * update - any content on page was changed (notify): function (type, arguments ...)
 * close - close current page and go back
 * redirect - redirect to link: function (href, keepMessages, force)
 * reload - browser page
 * refresh - current application
 * lock - lock to switch current application (override only throw redirect with force = true)
 * unlock - unlock ...
 */
Scalr.EventMessager.addEvents('update', 'close', 'redirect', 'reload', 'refresh', 'resize', 'lock', 'unlock', 'maximize');

Scalr.EventMessager.on = Ext.Function.createSequence(Scalr.EventMessager.on, function (event, handler, scope) {
	if (event == 'update' && scope)
		scope.on('destroy', function () {
			this.un('update', handler, scope);
		}, this);
});

Scalr.cache = {};
Scalr.regPage = function (type, fn) {
	Scalr.cache[type] = fn;
};

Scalr.state = {
	suspendPage: false,
	suspendPageForce: false
};

Scalr.flags = {};

Scalr.version = function (checkVersion) {
	try {
		var version = Scalr.InitParams.ui.version;
	} catch (e) {}
	return ( !version || version == checkVersion) ? true : false;
};

Ext.getBody().setStyle('overflow', 'hidden');
Ext.tip.QuickTipManager.init();

Ext.state.Manager.setProvider(new Ext.state.LocalStorageProvider({ prefix: 'scalr-' }));

Scalr.EventMessager.on('close', function(keepMessages) {
	Scalr.message.SetKeepMessages(Ext.isBoolean(keepMessages) ? keepMessages : true);

	if (history.length > 1)
		history.back();
	else
		document.location.href = "#/dashboard";
});

Scalr.EventMessager.on('redirect', function(href, keepMessages, force) {
	Scalr.message.SetKeepMessages(Ext.isBoolean(keepMessages) ? keepMessages : false);
	Scalr.state.suspendPageForce = Ext.isBoolean(force) ? force : false;
	document.location.href = href;
});

Scalr.EventMessager.on('lock', function() {
	Scalr.state.suspendPage = true;
	Scalr.application.MainWindow.disabledDockedToolbars(true);
});

Scalr.EventMessager.on('unlock', function() {
	Scalr.state.suspendPage = false;
	Scalr.application.MainWindow.disabledDockedToolbars(false);
});

Scalr.EventMessager.on('reload', function () {
	document.location.reload();
});

Scalr.EventMessager.on('refresh', function (forceReload) {
	// @TODO: forceReload
	window.onhashchange(true);
});

Scalr.EventMessager.on('resize', function () {
	Scalr.application.MainWindow.getLayout().onOwnResize();
});

Scalr.event.on('maximize', function () {
	var options = Scalr.application.MainWindow.getLayout().activeItem.scalrOptions;
	options.maximize = options.maximize == 'all' ? '' : 'all';
	Scalr.application.MainWindow.getLayout().onOwnResize();
});

Scalr.suspendPage = false;

Ext.Ajax.timeout = 60000;
