<?php

	class Scalr_SchedulerTask extends Scalr_Model
	{
		protected $dbTableName = 'scheduler';
		protected $dbPrimaryKey = 'id';
		protected $dbMessageKeyNotFound = 'Scheduler task #%s not found in database';

		const SCRIPT_EXEC = 'script_exec';
		const TERMINATE_FARM = 'terminate_farm';
		const LAUNCH_FARM = 'launch_farm';

		const STATUS_ACTIVE = "Active";
		const STATUS_SUSPENDED = "Suspended";
		const STATUS_FINISHED = "Finished";

		const TARGET_FARM = 'farm';
		const TARGET_ROLE = 'role';
		const TARGET_INSTANCE = 'instance';
		
		protected $dbPropertyMap = array(
			'id'			=> 'id',
			'name'			=> 'name',
			'type'			=> 'type',
			'target_id'		=> array('property' => 'targetId'),
			'target_type'	=> array('property' => 'targetType'),
			'start_time'		=> array('property' => 'startTime'),
			'end_time'		=> array('property' => 'endTime'),
			'last_start_time'	=> array('property' => 'lastStartTime'),
			'restart_every'	=> array('property' => 'restartEvery'),
			'config'		=> array('property' => 'config', 'type' => 'serialize'),
			'order_index'	=> array('property' => 'orderIndex'),
			'timezone'		=> 'timezone',
			'status'		=> 'status',
			'account_id'	=> array('property' => 'accountId'),
			'env_id'		=> array('property' => 'envId'),
		);

		public
			$id,
			$name,
			$type,
			$targetId,
			$targetType,
			$startTime,
			$endTime,
			$lastStartTime,
			$restartEvery,
			$config,
			$orderIndex,
			$timezone,
			$status,
			$accountId,
			$envId;
			
		/**
		 * 
		 * @return Scalr_SchedulerTask
		 */
		public static function init()
		{
			return parent::init();
		}

		public static function getTypeByName($name)
		{
			switch($name) {
				case self::SCRIPT_EXEC:
					return "Execute script";
				case self::TERMINATE_FARM:
					return "Terminate farm";
				case self::LAUNCH_FARM:
					return "Launch farm";
			}
		}
	}
