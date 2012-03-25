class ServiceProxy(object):
    
	def __init__(self):
		self.local = local()
    
	def __getattr__(self, name):
		try:
			self.__dict__['local'].method.append(name)
		except AttributeError:
			self.__dict__['local'].method = [name]
		return self
	
	def __call__(self, **kwds):
		try:
			req = json.dumps({'method': self.local.method[-1], 'params': kwds, 'id': time.time()})
			resp = json.loads(self.exchange(req))
			if 'error' in resp:
				error = resp['error']
				raise ServiceError(error.get('code'), error.get('message'), error.get('data'))
			return resp['result']
		finally:
			self.local.method = []
    
	def exchange(self, request):
		raise NotImplemented()


class ZmqServiceProxy(ServiceProxy):
	
	def __init__(self, endpoint, crypto_key=None):
		self.endpoint = endpoint		
		self.context = zmq.Context()
		super(ZmqServiceProxy, self).__init__()
	
	@property
	def conn(self):
		try:
			return self.local.conn
		except AttributeError:
			LOG.debug('Creating new REQ connection...')
			conn = zmq.Socket(self.context, zmq.REQ)
			conn.connect(self.endpoint)
			self.local.conn = conn 
			return self.conn
	
	def exchange(self, request):
		namespace = '/'.join(self.local.method[0:-1])
		self.conn.send_multipart([namespace, request])
		return self.conn.recv()
