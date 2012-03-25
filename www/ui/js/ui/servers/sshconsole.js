Scalr.regPage('Scalr.ui.servers.sshconsole', function (loadParams, moduleParams) {
	return Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		scalrOptions: {
			'maximize': 'all'
		},
		title: 'Servers &raquo; ' + moduleParams['serverId'] + ' &raquo; SSH console',
		tools: [{
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}],

		layout: {
			type: 'vbox',
			align: 'stretch',
			pack: 'start'
		},
		items: [{
			height: 30,
			border: false,
			bodyStyle: 'background-color: inherit',
			html:
				'IP: ' + moduleParams['remoteIp'] + ' &nbsp; Internal IP: ' + moduleParams['localIp'] + '<br />' +
				'Farm: ' + moduleParams['farmName'] + ' (ID: ' + moduleParams['farmId'] + ') ' + 'Role: ' + moduleParams['roleName'] + '<br /><br />'
		}, {
			flex: 1,
			border: false,
			bodyStyle: 'background-color: inherit',
			layout: 'fit',
			html: 'Loading, please wait ...',
			listeners: {
				afterrender: function () {
					Ext.Function.defer(function() {
						this.body.update(
							'<object ' +
								'code="com.mindbright.application.MindTerm" ' +
								'archive="/ui/java/mindterm3.2.jar" ' +
								'type="application/x-java-applet" ' +
								'width="' + this.body.getWidth() + '" height="' + this.body.getHeight() + '">' +
									'<param name="sepframe" value="false">' +
									'<param name="debug" value="false">' +
									'<param name="quiet" value="true">' +
									'<param name="menus" value="no">' +
									'<param name="exit-on-logout" value="true">' +
									'<param name="allow-new-server" value="false">' +
									'<param name="savepasswords" value="false">' +
									'<param name="verbose" value="false">' +
									'<param name="useAWT" value="false">' +
									'<param name="protocol" value="ssh2">' +
									'<param name="server" value="' + moduleParams['remoteIp'] + '">' +
									'<param name="port" value="' + moduleParams['port'] + '">' +
									'<param name="username" value="root">' +
									'<param name="auth-method" value="publickey">' +
									'<param name="fg-color" value="white">' +
									'<param name="bg-color" value="black">' +
									'<param name="private-key-str" value="' + moduleParams['key'] + '">' +
									'<param name="geometry" value="125x35">' +
							'</object>'
						);
					}, 3000, this);
				}
			}
		}]
	});
});
