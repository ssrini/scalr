<?php
	class Scalr_Scaling_Metric extends Scalr_Model
	{
		protected $dbTableName = 'scaling_metrics';
		protected $dbPrimaryKey = "id";
		protected $dbMessageKeyNotFound = "Metric #%s not found in database";

		protected $dbPropertyMap = array(
			'id'				=> 'id',
			'name'				=> array('property' => 'name', 'is_filter' => true),
			'client_id'			=> array('property' => 'clientId', 'is_filter' => true),
			'env_id'			=> array('property' => 'envId', 'is_filter' => true),
			'file_path'			=> array('property' => 'filePath'),
			'retrieve_method'	=> array('property' => 'retrieveMethod'),
			'calc_function'		=> array('property' => 'calcFunction'),
			'algorithm'			=> array('property' => 'algorithm'),
			'alias'				=> array('property' => 'alias')
		);
		
		public
			$id,
			$name,
			$clientId,
			$envId,
			$filePath,
			$retrieveMethod,
			$algorithm,
			$calcFunction,
			$alias;
	}
?>