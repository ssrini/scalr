<?php
	
	abstract class Event
	{
		public $SkipDeferredOperations = false;
		
		private $EventID;
		private $FarmID;
		
		/**
		 * 
		 * @var DBFarm
		 */
		public $DBFarm;
		
		/**
		 * constructor
		 * @return void
		 */
		public function __construct()
		{
			$this->EventID = Scalr::GenerateUID();
		}
		
		/**
		 * Set FarmID for Event
		 * @param integer $farm_id
		 * @return void
		 */
		public function SetFarmID($farm_id)
		{
			if ($this->FarmID === null)
			{
				$this->FarmID = $farm_id;
				if ($farm_id)
				{
					$this->DBFarm = DBFarm::LoadByID($farm_id);
				}
			}
			else
				throw new Exception("FarmID already set for this event");
		}
		
		public static function GetScriptingVars()
		{
			return array();
		}
		
		/**
		 * Returns Event FarmID
		 * @return integer $farm_id
		 */
		public function GetFarmID()
		{
			return $this->FarmID;
		}
		
		/**
		 * Returns event unique ID
		 * @return string
		 */
		public function GetEventID()
		{
			return $this->EventID;
		}
		
		/**
		 * Returns event name
		 *
		 * @return string
		 */
		public function GetName()
		{
			return str_replace(__CLASS__, "", get_class($this));
		}
	}
?>