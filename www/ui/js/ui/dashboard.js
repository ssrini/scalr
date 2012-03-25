Scalr.regPage('Scalr.ui.dashboard', function (loadParams, moduleParams) {
	//Scalr.message.Warning('Dashboard in development');

	//return Ext.create('Ext.container.Container', {
	return Ext.create('Ext.panel.Panel', {
		scalrOptions: {
			'maximize': 'all',
			'reload': false
		},
		bodyCls: 'scalr-ui-dashboard-welcome-body',
		title: 'Dashboard',
		bodyPadding: 20,
		border: true,

		/*layout: {
			type: 'column'
		},*/
		bodyStyle: 'font-size: 13px',
		title: 'Welcome to Scalr!',
		html:
			'<div><div class="scalr-ui-dashboard-welcome-quest" style="height: 85px; padding-top: 10px;" align="center">New to Scalr?</div>'+
			'<div>'+
				'<a href="#/environments/' + moduleParams['envId'] + '/edit" style="text-decoration: none;"><div class="scalr-ui-dashboard-welcome-back" style="float:left; width:32%; margin-right: 15px;">'+
					'<div class="scalr-ui-dashboard-welcome-numbers" style="margin:15px 0px 0px 15px; float: left; height: 99%;">1. </div>'+
					'<div style="margin:15px 10px 0px 65px;" align="center">'+
						'<div class="scalr-ui-dashboard-welcome-title">Add your cloud keys</div><br/>'+
						'<div class="scalr-ui-dashboard-welcome-desc">Username &gt; Options &gt; Configure</div>'+
						'<div><img src="ui/images/ui/dashboard/cloud_key.png" style="width:174px; height: 108px;"/></div>'+
					'</div>'+
				'</div></a>'+
				'<a href="#/farms/build" style="text-decoration: none;"><div class="scalr-ui-dashboard-welcome-back" style="float:left; width:32%; margin-right: 15px;">'+
					'<div class="scalr-ui-dashboard-welcome-numbers" style="margin:15px 0px 0px 15px; float: left; height: 99%;">2. </div>'+
					'<div style="margin:15px 10px 0px 65px;" align="center">'+
						'<div class="scalr-ui-dashboard-welcome-title">Create a new farm</div><br/>'+
						'<div class="scalr-ui-dashboard-welcome-desc">ServerFarms &gt; Build new</div>'+
						'<div><img src="ui/images/ui/dashboard/new_farm.png" style="width:174px; height: 108px;"/></div>'+
					'</div>'+
				'</div></a>'+
				'<a href="#/dnszones/view" style="text-decoration: none;"><div class="scalr-ui-dashboard-welcome-back" style="float:left; width:32%;">'+
					'<div class="scalr-ui-dashboard-welcome-numbers" style="margin:15px 0px 0px 15px; float: left; height: 99%;">3. </div>'+
					'<div style="margin:15px 10px 0px 65px;" align="center">'+
						'<div class="scalr-ui-dashboard-welcome-title">Set up your domain name</div><br/>'+
						'<div class="scalr-ui-dashboard-welcome-desc">Websites &gt; DNS Zones</div>'+
						'<div><img src="ui/images/ui/dashboard/dns_zones.png" style="width:174px; height: 108px;"/></div>'+
					'</div>'+
				'</div></a>'+
			'</div></div>' +
			'<div style="margin-top: 240px;"><div class="scalr-ui-dashboard-welcome-quest" style="height: 85px;" align="center">Already a pro?</div>'+
			'<div>'+
				'<a href="#/scripts/create" style="text-decoration: none;"><div class="scalr-ui-dashboard-welcome-back" style="float:left; width:32%; padding: 45px 0px 0px 40px; margin-right: 15px;">'+
					'<div style="float: left; height: 99%;"><img src="ui/images/ui/dashboard/add_new_script.png" style="width:99px; height: 98px;"/> </div>'+
					'<div class="scalr-ui-dashboard-welcome-title" style="margin: 25px 0px 0px 110px;">Try our Scripts</div><br/>'+
					'<div class="scalr-ui-dashboard-welcome-desc" style="margin-left:110px;">Scripts &gt; Add new</div>'+
				'</div></a>'+
				'<a href="#/core/api" style="text-decoration: none;"><div class="scalr-ui-dashboard-welcome-back" style="float:left; width:32%; padding: 45px 0px 0px 40px; margin-right: 15px;">'+
					'<div style="float: left; height: 99%;"><img src="ui/images/ui/dashboard/api_access.png" style="width:99px; height: 98px;"/> </div>'+
					'<div class="scalr-ui-dashboard-welcome-title" style="margin: 25px 0px 0px 110px;">Use our API</div><br/>'+
					'<div class="scalr-ui-dashboard-welcome-desc" style="margin-left:110px;">UserName &gt; API Access</div>'+
				'</div></a>'+
				'<a href="#/services/chef/servers/view" style="text-decoration: none;"><div class="scalr-ui-dashboard-welcome-back" style="float:left; width:32%; padding: 45px 0px 0px 40px;">'+
					'<div style="float: left; height: 99%;"><img src="ui/images/ui/dashboard/chef_servers.png" style="width:99px; height: 98px;"/> </div>'+
					'<div class="scalr-ui-dashboard-welcome-title" style="margin: 25px 0px 0px 110px;">Get started with Chef</div><br/>'+
					'<div class="scalr-ui-dashboard-welcome-desc" style="margin-left:110px;">Roles &gt; Chef servers</div>'+
				'</div></a>'+
			'</div></div>'
		/*html: "Scalr is a Cloud Management tool that brings <b>automation to web applications like no other</b>."+
		"Auto-scaling, high availability, fault tolerance, backups, multi-cloud deployments, hybrid cloud bursting are all supported out-of-the-box."+
		"<br><br>We've prepared a <a href='http://wiki.scalr.net/Getting_Started' target='_blank'>nice & easy guide</a> for you to get started. "+
		"Open it in a new tab or simply follow: </br></br>" +
		"<br><br><b>Six steps to successfully scaling your setup with Scalr</b>:</br></br>"+
		"<br>&bull; Add your cloud keys: <a href='https://my.scalr.net/#/environments/view'>https://my.scalr.net/#/environments/view'</a> - click Options -> Configure</br>"+
		"<br>&bull; Create a server farm: <a href='https://my.scalr.net/#/farms/build'>https://my.scalr.net/#/farms/build</a> - give your farm a name, click on the Roles tab, and add nginx, apache, and mysql</br>"+
		"<br>&bull; Click each Role icon to configure Backups, Instance size, and more. Save and launch farm.</br>"+
		"<br>&bull; Setup your domain name: <a href='https://my.scalr.net/#/dnszones/view'>https://my.scalr.net/#/dnszones/view</a></br>"+
		"<br>&bull; Upload your site code: <a href='https://my.scalr.net/#/dm/sources/view'>https://my.scalr.net/#/dm/sources/view</a> - click add</br>"+
		"<br>&bull; Grab some beer and start the party!"*/
	})
});
