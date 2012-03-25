<?php

class Scalr_Util_DateTime
{
	public static function getTimezoneOffset($remoteTz, $originTz)
	{
		if (is_null($originTz)) {
			$originTz = date_default_timezone_get();
			if (! is_string($originTz))
				return false;
		}

		if (is_null($remoteTz)) {
			$remoteTz = date_default_timezone_get();
			if (! is_string($remoteTz))
				return false;
		}

		if (get_class($remoteTz) == 'DateTimeZone')
			$remoteDtz = $remoteTz;
		else
			$remoteDtz = new DateTimeZone($remoteTz);

		if (get_class($originTz) == 'DateTimeZone')
			$originDtz = $originTz;
		else
			$originDtz = new DateTimeZone($originTz);

		$originDt = new DateTime('now', $originDtz);
		$remoteDt = new DateTime('now', $remoteDtz);
		return $originDtz->getOffset($originDt) - $remoteDtz->getOffset($remoteDt);
	}

	public static function convertDateTime(DateTime $dt, $remoteTz = NULL, $originTz = NULL)
	{
		$offset = self::getTimezoneOffset($remoteTz, $originTz);
		$interval = new DateInterval('PT' . abs($offset) . 'S');
		if ($offset < 0)
			$dt->add($interval);
		else
			$dt->sub($interval);

		return $dt;
	}

	public static function convertTz($value, $format = 'M j, Y H:i:s')
	{
		if (is_integer($value))
			$value = "@{$value}";

		$dt = new DateTime($value);

		if ($dt && $dt->getTimestamp()) {
			if (Scalr_UI_Request::getInstance()->getEnvironment()) {
				$timezone = Scalr_UI_Request::getInstance()->getEnvironment()->getPlatformConfigValue(Scalr_Environment::SETTING_TIMEZONE);
				if (! $timezone)
					$timezone = 'UTC';
				self::convertDateTime($dt, $timezone, $dt->getTimezone());
			}
			
			return $dt->format($format);
		} else
			return NULL;
	}
	
	public static function getTimezones()
	{
		$timezones = array();
		foreach (DateTimeZone::listAbbreviations() as $timezoneAbbreviations) {
			foreach ($timezoneAbbreviations as $value) {
				if (preg_match( '/^(America|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific|Australia)\//', $value['timezone_id']))
					$timezones[$value['timezone_id']] = $value['offset'];
			}
		}

		@ksort($timezones);
		return array_keys($timezones);
	}
}
