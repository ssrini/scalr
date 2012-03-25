<?php

interface Scalr_Cache_Interface
{
	public function get($key);

	public function set($key, $value, $expire);

	public function check($key);
}
