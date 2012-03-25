#!/usr/bin/python

import os
import re
import imp
import logging
import subprocess
import cStringIO
import shutil
import httplib
import urllib2
from ConfigParser import ConfigParser
from optparse import OptionParser
import sys
import pwd
import uuid


gopts = None
logger = None
scalr_repo_package_url = 'http://apt.scalr.net/scalr-repository_0.2_all.deb'
result_msg_file = '/tmp/migration-result.xml'
host_conf = None
farm_conf = None
mysql_conf = None
master_conf = None
behaviour = None
role_name = None
snmp_community_name = None
mysql_ebs_volume_id = None
mysql_storage = None
ec2_access_key = None
ec2_secret_key = None

class MigrationException(Exception):
	pass

def main():
	global logger, host_conf, farm_conf, mysql_conf, \
			behaviour, role_name, snmp_community_name, \
			mysql_ebs_volume_id, mysql_storage, \
			ec2_access_key, ec2_secret_key
	
	''' Parse command-line options and configurations '''
	parse_opts()

	logging.basicConfig(format="%(asctime)s - %(levelname)s - %(message)s", 
					stream=sys.stdout, level=logging.DEBUG)
	logger = logging.getLogger(__name__)
	logger.info('Reading scalr-ami-scripts configuration files')
	
	host_conf = parse_amiscripts_config('/etc/aws/host.conf')
	farm_conf = parse_amiscripts_config('/etc/aws/farmconfig')

	behaviour = host_conf['SERVER_ROLE']
	if behaviour == 'base':
		behaviour = ''
	
	role_name = host_conf['USER_ROLE']
	snmp_community_name = farm_conf['ACCESS_HASH']
	
	if behaviour == 'mysql':
		if host_conf['MYSQL_ROLE'] != 'master':
			raise MigrationException('MySQL migration available only for replication master')
		mysql_conf = parse_amiscripts_config('/etc/aws/mysql.conf')
		mysql_storage = mysql_conf['MYSQL_STORAGE']
		if mysql_storage == 'ebs':
			s3cmd_ini = ConfigParser()
			s3cmd_ini.read('/etc/aws/keys/s3cmd.cfg')
			ec2_access_key = s3cmd_ini.get('default', 'access_key')
			ec2_secret_key = s3cmd_ini.get('default', 'secret_key')
			mysql_ebs_volume_id = mysql_conf['MYSQL_EBS_VOL_ID']
		else:
			raise MigrationException('Scalr MySQL storage type is obsolete and cannot be converted '
						'in an automated manner. Contact Scalr support team to help you ' 
						'with this migration')
	logger.info('Migration is possible')
	if gopts.noop:
		sys.exit()

	
	if os.path.exists('/etc/init.d/scalarizr'):
		system2(('/etc/init.d/scalarizr', 'stop'))
	system2(('/etc/init.d/snmpd', 'stop'))
	try:
		''' Install and configure scalarizr '''		
		install_scalarizr()
		configure_scalarizr_base()
		
		if not '/var/lib/python-support/python2.5' in sys.path:
			sys.path.append('/var/lib/python-support/python2.5')
		#print os.path.exists('/var/lib/python-support/python2.5/scalarizr')
		#print sys.path
		#print sys.modules
		mod_szr = imp.load_module('scalarizr', *imp.find_module('scalarizr'))
				
		msg_data = dict(result='ok')
				
		if behaviour == 'mysql':
			if mysql_storage == 'ebs':
				msg_data['mysql'] = configure_scalarizr_mysql_ebs()
			else:
				raise MigrationException('Unknown storage: %s', mysql_storage)
		elif behaviour == 'app':
			configure_scalarizr_apache()

		logger.info('Starting Scalarizr')
		system2(('/etc/init.d/scalarizr', 'start'))
		
		''' Remove ami-scripts init '''
		remove_amiscripts_init()

		''' Notify Scalr '''
		logger.info('Sending notification to Scalr')
		Message = __import__('scalarizr.messaging', fromlist=['Message']).Message
		msg = Message('AmiScriptsMigrationResult', 
					dict(server_id=gopts.server_id, szr_version=mod_szr.__version__),
					msg_data)
		msg.id = str(uuid.uuid4())
		file_put_contents(result_msg_file, str(msg))				
		system2(('/usr/local/bin/szradm', '--msgsnd', 
				'--queue', 'control',
				'--msgfile', result_msg_file))
		
	except:
		system2(('/etc/init.d/snmpd', 'start'))
		system2(('/etc/init.d/scalarizr', 'stop'), raise_exc=False)
		raise
	finally:
		if os.path.exists(result_msg_file):
			os.remove(result_msg_file)
	

def parse_opts():
	global gopts
	parser = OptionParser()
	
	required = parser.add_option_group('Required parameters')
	required.add_option('-s', '--server-id', dest='server_id', action='store', help='Scalr server-id')
	required.add_option('-k', '--crypto-key', dest='crypto_key', action='store', help='Scalr crypto key')
	
	optional = parser.add_option_group('Optional parameters')
	optional.add_option('--noop', dest='noop', action='store_true', 
					help='No-operation mode, only check migration possibility')
	optional.add_option('-o',  dest='optionals', action='append', help='.ini option key=value')
	
	parser.parse_args()
	
	if not (parser.values.server_id and parser.values.crypto_key):
		print 'Required option missed'
		parser.print_usage()
		sys.exit(1)
		
	gopts = parser.values


def install_scalarizr():
	logger.info('Adding Scalr repository %s', scalr_repo_package_url)
	scalr_repo_package = os.path.join('/tmp', os.path.basename(scalr_repo_package_url))
	if not os.path.exists(scalr_repo_package):
		resp = urllib2.urlopen(scalr_repo_package_url)
		file_copy(resp, scalr_repo_package)
	system2(('dpkg', '-i', scalr_repo_package))
	os.remove(scalr_repo_package)
	
	# Install scalarizr
	logger.info('Installing scalarizr package')
	system2(('apt-get', 'update'))
	system2(('apt-get', 'install', '-y', 'scalarizr', 'scalarizr-ec2'), 
			env={'DEBIAN_FRONTEND': 'noninteractive', 'DEBIAN_PRIORITY': 'critical', 'PATH': os.environ['PATH']})


def configure_scalarizr_base():
	logger.info('Configuring scalarizr')
	args = ['scalarizr', '--configure', '-y']
	opts = {
		'server-id': gopts.server_id,
		'crypto-key': gopts.crypto_key,
		'behaviour': behaviour,
		'platform': 'ec2',
		'role-name' : role_name,
		'snmp.community-name' : snmp_community_name
	}
	if gopts.optionals:
		opts.update(dict(o.split('=') for o in gopts.optionals))
	for item in opts.items():
		args.extend(('-o', '='.join(item)))
		
	system2(args)		
	file_put_contents('/etc/scalr/private.d/.state', 'running')
	if os.path.exists('/etc/init.d/ec2-every-startup'):
		os.remove('/etc/init.d/ec2-every-startup')
	

def configure_scalarizr_mysql_ebs():
	vol_conf = dict(type='ebs', id=mysql_ebs_volume_id, device='/dev/sdo', mpoint='/mnt/dbstorage')	

	logger.info('Configuring scalarizr mysql-ebs')
	system2(('/etc/init.d/mysql', 'stop'))	

	bus = __import__('scalarizr.bus').bus
	ScalarizrCnf = __import__('scalarizr.config').ScalarizrCnf
	Configuration = __import__('scalarizr.libs.metaconf', fromlist=['Configuration']).Configuration
	Storage = __import__('scalarizr.storage', fromlist=['Storage']).Storage
	Ec2Platform = __import__('scalarizr.platform.ec2', fromlist=['Ec2Platform']).Ec2Platform
	mysql_hdlr = __import__('scalarizr.handlers.mysql', fromlist=['get_handlers'])

	cnf = ScalarizrCnf('/etc/scalr')
	cnf.bootstrap()
	bus.cnf = cnf
	bus.platform = Ec2Platform()
	bus.platform.set_access_data(dict(key_id=ec2_access_key, key=ec2_secret_key))


	''' Patch my.cnf '''
	logger.info('Patching my.cnf')
	mycnf = Configuration('mysql')
	mycnf.read('/etc/mysql/my.cnf')

	mycnf.set('mysqld/server-id', '1', True)
	mycnf.set('mysqld/datadir', '/mnt/dbstorage/mysql-data/')
	mycnf.set('mysqld/log_bin', '/mnt/dbstorage/mysql-misc/binlog')

	if os.path.exists('/mnt/mysql-data/mysql/mysql'):
		logger.info('Moving mysql data files')
		for file in os.listdir('/mnt/mysql-data/mysql'):
			if file != 'mysql':
				src = os.path.join('/mnt/mysql-data/mysql', file)
				dst = os.path.join('/mnt/mysql-data', file)
				logger.debug('Moving %s -> %s', src, dst)
				shutil.move(src, dst)
		for file in os.listdir('/mnt/mysql-data/mysql/mysql'):
			src = os.path.join('/mnt/mysql-data/mysql/mysql', file)
			dst = os.path.join('/mnt/mysql-data/mysql', file)
			logger.debug('Moving %s -> %s', src, dst)
			shutil.move(src, dst)
		shutil.rmtree('/mnt/mysql-data/mysql/mysql')

	if os.path.exists('/mnt/mysql-misc/logs'):
		logger.info('Moving mysql binlog files')
		for file in os.listdir('/mnt/mysql-misc/logs'):
			shutil.copy2(os.path.join('/mnt/mysql-misc/logs', file), 
					os.path.join('/mnt/mysql-misc', file.replace('mysql-bin', 'binlog')))
		shutil.rmtree('/mnt/mysql-misc/logs')
	
		fp = open('/mnt/mysql-misc/binlog.index')
		binlog = fp.readlines()
		fp.close()
		fp = open('/mnt/mysql-misc/binlog.index', 'w')
		for line in binlog:
			fp.write(line.replace('/mnt/mysql-misc/logs/mysql-bin', '/mnt/dbstorage/mysql-misc/binlog'))
		fp.close()
	
	if os.path.exists('/mnt/mysql-misc'):
		system2(('chown', '-R', 'mysql:mysql', '/mnt/mysql-misc'))
	if os.path.exists('/mnt/mysql-data'):
		system2(('chown', '-R', 'mysql:mysql', '/mnt/mysql-data'))
	
	logger.info('Fixing fstab')
	fp = open('/etc/fstab')
	fstab = fp.readlines()
	fp.close()
	fp = open('/etc/fstab', 'w')
	for line in fstab:
		if line.startswith('/dev/sdo') or line.startswith('/dev/STORAGE/lvol'):
			continue
		fp.write(line)
	fp.close()
	
	logger.info('Re-mounting storage')
	if os.path.exists('/mnt/privated.img'):
		system2(('umount', '/mnt/privated.img'))
		shutil.move('/mnt/privated.img', '/root')
		
	system2(('sync'))
	if '/dev/sdo' in file_get_contents('/proc/mounts'):	
		system2(('umount', '/dev/sdo'))
	if not os.path.exists('/mnt/dbstorage'):
		os.makedirs('/mnt/dbstorage')
	if '/dev/sdo' not in file_get_contents('/proc/mounts'):
		system2(('mount', '/dev/sdo', '/mnt/dbstorage'))
	
	if os.path.exists('/root/privated.img'):
		shutil.move('/root/privated.img', '/mnt')
		system2(('mount', '/mnt/privated.img', '/etc/scalr/private.d', '-o', 'loop'))
	
	mycnf.write('/etc/mysql/my.cnf')
	if os.path.exists('/etc/mysql/conf.d/farm-replication.cnf'):
		os.remove('/etc/mysql/conf.d/farm-replication.cnf')
	
	
	''' Re-generate MySQL system users '''
	hdlr = mysql_hdlr.get_handlers()[0]	
	hdlr._mysql_config = mycnf
	hdlr.storage_vol = Storage.create(vol_conf)
	
	logger.info('Regenerating Scalr mysql system users')
	passwds = hdlr._add_mysql_users('scalr', 'scalr_repl', 'scalr_stat')
	mysql_hdlr_conf = {'mysql' : {
		'replication_master' : '1',
		'root_password' : passwds[0],
		'repl_password' : passwds[1],
		'stat_password' : passwds[2]
	}}
	cnf.update_ini('mysql', mysql_hdlr_conf)


	''' Create data bundle '''
	logger.info('Creating mysql data bundle')
	snap, log_file, log_pos = hdlr._create_snapshot('scalr', passwds[0])


	''' Update configurations '''
	mysql_hdlr_conf['mysql'].update({
		'log_file' : log_file,
		'log_pos' : log_pos
	})
	cnf.update_ini('mysql', mysql_hdlr_conf)
	
	logger.info('Updating mysql storage configuration')
	storage_conf_dir = '/etc/scalr/private.d/storage'
	vol_conf_path = os.path.join(storage_conf_dir, 'mysql.json')
	snap_conf_path = os.path.join(storage_conf_dir, 'mysql-snap.json')
	if not os.path.exists(storage_conf_dir):
		os.makedirs(storage_conf_dir)
	
	Storage.backup_config(snap.config(), snap_conf_path)
	Storage.backup_config(vol_conf, vol_conf_path)
	
	''' Return result '''
	return dict(
		mysql_hdlr_conf['mysql'],
		snapshot_config=snap.config(),
		volume_config = vol_conf 
	)
	
def configure_scalarizr_apache():
	logger.info('Configuring scalarizr app-apache')	

	QueryEnv = __import__('scalarizr.queryenv').QueryEnvService
	ScalarizrCnf = __import__('scalarizr.config').ScalarizrCnf
	cnf = ScalarizrCnf('/etc/scalr')
	cnf.bootstrap()

	queryenv = QueryEnv(
			cnf.rawini.get('general', 'queryenv_url'), 
			gopts.server_id, 
			cnf.key_path(cnf.DEFAULT_KEY))
	vhosts = queryenv.list_virtual_hosts()

	logger.info('Removing virtual hosts from /etc/apache2/sites-enabled. '
			'All scalr-managed virtual hosts will be available at /etc/scalr/private.d/vhosts')
	for name in os.listdir('/etc/apache2/sites-enabled'):
		if name == '000-default':
			continue
		logger.debug('Removing %s', name)
		os.unlink(os.path.join('/etc/apache2/sites-enabled', name))


def remove_amiscripts_init():
	global logger
	
	logger.info('Removing scalr-ami-scripts init from /etc/rc.local')
	contents = file_get_contents('/etc/rc.local')
	contents = re.sub(r'(\[ -x /usr/local/aws/bin)', r'#\1', contents)
	file_put_contents('/etc/rc.local', contents)

def write_option(filename, section, option, value):
	pass

def file_copy(src, dst):
	src_fp = open(src, 'r') if isinstance(src, basestring) else src
	dst_fp = open(dst, 'w') if isinstance(dst, basestring) else dst
	try:
		while True:
			buf = src_fp.read(8096)
			if not buf:
				break
			dst_fp.write(buf)
	finally:
		dst_fp.close()
		src_fp.close()
		
def file_put_contents(file, contents):
	fp = open(file, 'w')
	fp.write(contents)
	fp.close()

def file_get_contents(file):
	fp = None
	try:
		fp = open(file, 'r')
		return fp.read()
	finally:
		if fp:
			fp.close()


class ConfigFileDict(dict):
	filename = None
	def __getitem__(self, key):
		if not self.has_key(key):
			raise KeyError("File " + self.filename + " has no option '" + key + "'")
		return dict.__getitem__(self, key)

def parse_amiscripts_config(filename):
	fp = open(filename)
	ret = ConfigFileDict(line.strip().split('=') for line in fp)
	ret.filename = filename
	fp.close()
	return ret


class PopenError(BaseException):
	
	def __str__(self):
		if len(self.args) >= 5:
			args = [self.error_text or '']
			args += [self.proc_args[0] if hasattr(self.proc_args, '__iter__') else self.proc_args.split(' ')[0]]
			args += [self.returncode, self.out, self.err, self.proc_args]

			ret = '%s %s (code: %s) <out>: %s <err>: %s <args>: %s' % tuple(args)
			return ret.strip()
		else:
			return self.error_text
	
	@property
	def error_text(self):
		return self.args[0]
	
	@property
	def out(self):
		return self.args[1]
	
	@property
	def err(self):
		return self.args[2]

	@property
	def returncode(self):
		return self.args[3]
	
	@property
	def proc_args(self):
		return self.args[4]

def system2(*popenargs, **kwargs):
	logger 		= kwargs.get('logger', logging.getLogger(__name__))
	warn_stderr = kwargs.get('warn_stderr')
	raise_exc   = kwargs.get('raise_exc', kwargs.get('raise_error',  True))
	ExcClass 	= kwargs.get('exc_class', PopenError)
	error_text 	= kwargs.get('error_text')
	input 		= None
	
	if kwargs.get('err2out'):
		# Redirect stderr -> stdout
		kwargs['stderr'] = subprocess.STDOUT
		
	if not 'stdout' in kwargs:
		# Capture stdout
		kwargs['stdout'] = subprocess.PIPE
		
	if not 'stderr' in kwargs:
		# Capture stderr
		kwargs['stderr'] = subprocess.PIPE
		
	if isinstance(kwargs.get('stdin'),  basestring):
		# Pass string into stdin
		input = kwargs['stdin']
		kwargs['stdin'] = subprocess.PIPE
		
	if len(popenargs) > 0 and hasattr(popenargs[0], '__iter__'):
		# Cast arguments to str
		popenargs = list(popenargs)
		popenargs[0] = tuple('%s' % arg for arg in popenargs[0])
		
	if kwargs.get('shell'):
		# Set en_US locale
		if not 'env' in kwargs:
			kwargs['env'] = {}
		kwargs['env']['LANG'] = 'en_US'
		
	for k in ('logger', 'err2out', 'warn_stderr', 'raise_exc', 'raise_error', 'exc_class', 'error_text'):
		try:
			del kwargs[k]
		except KeyError:
			pass
		
	logger.debug('system: %s' % (popenargs[0],))
	p = subprocess.Popen(*popenargs, **kwargs)
	out, err = p.communicate(input=input)
	
	if out:
		logger.debug('stdout: ' + out)
	if err:
		logger.log(logging.WARN if warn_stderr else logging.DEBUG, 'stderr: ' + err)
	if p.returncode and raise_exc:
		raise ExcClass(error_text, out.strip(), err and err.strip() or '', p.returncode, popenargs[0])

	return out, err, p.returncode




if __name__ == '__main__':
	try:
		main()
	except MigrationException, e:
		print >> sys.stderr, str(e)
