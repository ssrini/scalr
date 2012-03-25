<?php

class Scalr_Cache
{
	private static $cache = array();

	const CACHE_MEMCACHE = 'memcache';
	const CACHE_FILE = 'file';

	public static function NewCache($cacheType)
	{
		if (! self::$cache[$cacheType]) {
			if ($cacheType == Scalr_Cache::CACHE_MEMCACHE)
				self::$cache[$cacheType] = new Scalr_Cache_Memcache();
			elseif ($cacheType == Scalr_Cache::CACHE_FILE)
				self::$cache[$cacheType] = new Scalr_Cache_File();
		}

		return self::$cache[$cacheType];
	}
}
