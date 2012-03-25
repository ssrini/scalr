<?php

class Scalr_Cache_File implements Scalr_Cache_Interface
{
	private function getFilePath($key)
	{
		return CACHEPATH."/scalr_cache_file.{$key}.cache";
	}

	public function get($key)
	{
		return null;
		// TODO
		/*$fileCache = $this->getFilePath($key);

		if (file_exists($fileCache)) {
			clearstatcache();

			$fc = file_get_contents($fileCache);


			$time = filemtime($fileCache);

			if ($time > time()-CONFIG::$AJAX_PROCESSLIST_CACHE_LIFETIME) //TODO: Move to config
			{
				readfile($plist_cache);
				exit();
			}

		*/
	}

	public function check($key)
	{
		return false;
	}

	public function set($key, $value, $expire)
	{
		return true;
	}
}
