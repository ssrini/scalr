#!/usr/bin/env python

import binascii
import cStringIO
import ConfigParser
import optparse
import logging
import sys
import subprocess
import os
import warnings

warnings.filterwarnings("ignore")

try:
	import paramiko
	from paramiko import rsakey
except ImportError:
	print 'paramiko not installed. apt-get install python-paramiko'
	sys.exit(1)
try:
	import MySQLdb
except ImportError:
	print 'MySQLdb not installed. apt-get install python-mysqldb'
	sys.exit(1)
try:
	import M2Crypto
	from M2Crypto import EVP
except ImportError:
	print 'M2Crypto mot installed. apt-get install python-m2crypto'
	sys.exit(1)


logging.basicConfig(stream=sys.stderr, level=logging.INFO)
LOG = logging.getLogger('root')

BASE_DIR = os.path.abspath(os.path.abspath(__file__) + '/../..')
CONN = None 
CRYPTO_KEY = None 
USERNAME = 'nimda'
PASSWORD = 'scalrpassw0rd'
	
def main():
	parser = optparse.OptionParser()
	parser.add_option('-e', '--endpoint', dest='endpoint', help='New Scalr endpoint (ex: https://custom.scalr.net)')
	parser.add_option('-f', '--farm-id', dest='farm_id', help='Farm ID')
	parser.add_option('-s', '--server-id', dest='server_id', help='Server ID')
	opts = parser.parse_args()[0]
	if not (opts.farm_id or opts.server_id):
		print '--farm-id or --server-id required'
		sys.exit(1)
	if not (opts.endpoint):
		print '--endpoint required'
		sys.exit(1)
	
	init_crypto()
	init_db_connection()
	servers = list_servers(opts.farm_id, opts.server_id)
	LOG.info('Processing...')
	for server in servers:
		LOG.info('Updating %s (%s)', server['public_ip'], server['id'])
		try:
			update_server(server, endpoint=opts.endpoint)
			if not LOG.isEnabledFor(logging.DEBUG):
				sys.stdout.write('.')
		except:
			LOG.exception("Can't update %s (%s)", server['public_ip'], server['id'])
	LOG.info("Done")
	

def list_servers(farm_id=None, server_id=None):
	LOG.info('Listing servers (farm_id: %s, server_id: %s)', farm_id, server_id)
	assert farm_id or server_id
	sql = "SELECT * FROM servers WHERE status = 'Running'"
	if farm_id:
		sql += " AND farm_id = %d" % int(farm_id)
	elif server_id:
		sql += " AND server_id = '%s'" % server_id

	cur = create_cursor()
	cur.execute(sql)
	rows = cur.fetchall()
	
	result = []
	for row in rows:
		cur = create_cursor()
		cur.execute('SELECT * FROM farm_roles WHERE id = %d' % row['farm_roleid'])
		farm_role = cur.fetchone()
		
		result.append({
			'id': row['server_id'],
			'public_ip': row['remote_ip'],
			'local_ip': row['local_ip'],
			'ssh_private_key': get_ssh_private_key(
									'FARM-%s' % row['farm_id'], 
									farm_role['cloud_location'], 
									farm_role['platform'])
		})
	LOG.info('Found %d server(s)', len(result))
	return result	
	
	
def update_server(server, endpoint):
	ssh = paramiko.SSHClient()
	ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
	LOG.debug('Opening SSH connection root@%s', server['public_ip'])
	print server['ssh_private_key']
	pkey = rsakey.RSAKey(file_obj=cStringIO.StringIO(server['ssh_private_key']))
	#pkey = rsakey.RSAKey(file_obj=open('marat.pem'))
	ssh.connect(server['public_ip'], port=70, username=USERNAME, pkey=pkey, timeout=30)
	#ssh.connect('local.webta.net', username='nimda', pkey=pkey, timeout=30)
	private_cnf = '/etc/scalr/private.d/config.ini'
	
	chan = ssh.get_transport().open_session()
	chan.exec_command("sudo -S python -c '"
		"import os, ConfigParser; "
		"private_cnf = \""+private_cnf+"\"; "
		"endpoint = \""+endpoint+"\"; "
		"cnf = ConfigParser.ConfigParser(); "
		"cnf.readfp(open(private_cnf)); "
		"cnf.set(\"general\", \"queryenv_url\", endpoint + \"/query-env\"); "
		"cnf.set(\"messaging_p2p\", \"producer_url\", endpoint + \"/messaging\"); "
		"cnf.write(open(private_cnf, \"w\")); "
		"'")
	stdin = chan.makefile('wb', -1)
	stderr = chan.makefile_stderr('rb', -1)
	stdin.write(PASSWORD + '\n')
	status = chan.recv_exit_status()
	if status:
		LOG.warn('Failed to patch %s: %s', private_cnf, ''.join(stderr.readlines()))
	
	LOG.info('Restarting Scalarizr')
	chan = ssh.get_transport().open_session()
	chan.exec_command('sudo -S /etc/init.d/scalarizr restart')
	chan.send(PASSWORD + '\n')
	status = chan.recv_exit_status()
	if status:
		LOG.warn('Scalarizr restart on %s failed with code: %s', 
				server['public_ip'], status)
			
	
def get_ssh_private_key(name, region, platform):
	cur = create_cursor()
	cur.execute("SELECT * FROM ssh_keys "
			"WHERE cloud_key_name = '%s' AND cloud_location = '%s' AND platform = '%s'" % (name, region, platform))
	row = cur.fetchone()
	if row:
		return decrypt(row['private_key'])
	raise LookupError('SSH key not found (name: %s, region: %s, platform: %s)' % (name, region, platform))
	
	
		
def init_db_connection():
	global CONN
	LOG.debug('Initializing database connection')
	conf = ConfigParser.ConfigParser()
	conf.read(BASE_DIR + '/etc/config.ini')
	db = dict((key, value.replace('"', '')) for key, value in conf.items('db'))
	CONN = MySQLdb.connect(host=db['host'], user=db['user'], passwd=db['pass'], db=db['name'])


def create_cursor():
	return CONN.cursor(MySQLdb.cursors.DictCursor)	


def init_crypto():
	global CRYPTO_KEY
	CRYPTO_KEY = open(BASE_DIR + '/etc/.cryptokey').read().strip()


crypto_algo = dict(name="des_ede3_cfb", key_size=24, iv_size=8)


def _init_cipher(key, op_enc=1):
	skey = key[0:crypto_algo["key_size"]] 	# Use first n bytes as crypto key
	iv = key[-crypto_algo["iv_size"]:] 		# Use last m bytes as IV
	return EVP.Cipher(crypto_algo["name"], skey, iv, op_enc)


def encrypt (s):
	c = _init_cipher(CRYPTO_KEY, 1)
	ret = c.update(s)
	ret += c.final()
	del c
	return binascii.b2a_base64(ret)
	
def decrypt (s):
	proc = subprocess.Popen(('/usr/bin/php', '-q', BASE_DIR + '/bin/decrypt.php'), stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
	return proc.communicate(s)[0]
	'''
	c = _init_cipher(CRYPTO_KEY, 0)
	ret = c.update(binascii.a2b_base64(s))
	ret += c.final()
	del c
	return ret
	'''


if __name__ == '__main__':
	main()
