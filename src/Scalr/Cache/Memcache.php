<?php

class Scalr_Cache_Memcache implements Scalr_Cache_Interface
{
	private $connection;

	public function __construct()
	{
		$this->connection = new Memcache;
		if (! $this->connection->connect('localhost', 11211)) {
			// TODO: destroy object
			$this->connection = null; // disabled cache
		}
	}

	public function get($key)
	{
		if ($this->connection)
			return $this->connection->get($key);

		return null;
	}

	public function check($key)
	{
		if ($this->connection)
			return $this->connection->get($key) !== FALSE ? true : false;

		return false;
	}

	public function set($key, $value, $expire)
	{
		if ($this->connection)
			return $this->connection->set($key, $value, 0, $expire);

		return true;
	}
}
