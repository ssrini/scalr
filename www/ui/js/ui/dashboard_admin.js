Scalr.regPage('Scalr.ui.dashboard_admin', function (loadParams, moduleParams) {
	//Scalr.message.Warning('Dashboard in development');

	//return Ext.create('Ext.container.Container', {
	return Ext.create('Ext.panel.Panel', {
		scalrOptions: {
			'maximize': 'all',
			'reload': false
		},
		title: 'Dashboard',
		bodyPadding: 20,
		border: true,

		/*layout: {
			type: 'column'
		},*/
		bodyStyle: 'font-size: 13px',
		title: 'Welcome to Scalr Admin!',
		html: "Scalr Admin"
	})
});
