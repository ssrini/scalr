<?php
class Scalr_UI_Controller_Services_Mongodb extends Scalr_UI_Controller
{
	public static function getPermissionDefinitions()
	{
		return array();
	}
	
	/**
	 * @return DBFarmRole
	 */
	public function getFarmRole()
	{
		$this->request->defineParams(array(
			'farmId' => array('type' => 'int')
		));

		$dbFarm = DBFarm::LoadByID($this->getParam('farmId'));
		$this->user->getPermissions()->validate($dbFarm);

		foreach ($dbFarm->GetFarmRoles() as $dbFarmRole) {
			if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MONGODB))
				return $dbFarmRole;
		}

		throw new Exception("Role not found");
	}

	public function statusAction()
	{
		$dbFarmRole = $this->getFarmRole();

		$moduleParams['farmRoleId'] = $dbFarmRole->ID;
		$moduleParams['farmHash'] = $dbFarmRole->GetFarmObject()->Hash;
		$moduleParams['mongodb'] = array();
		$moduleParams['mongodb']['password'] = $dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_PASSWORD);
		$moduleParams['mongodb']['status'] = $dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_CLUSTER_STATUS);

		$serverStatus = array();
		foreach ($dbFarmRole->GetServersByFilter(null, array('status' => array(SERVER_STATUS::TERMINATED, SERVER_STATUS::PENDING_TERMINATE))) as $server) {
			$shardIndex = $server->GetProperty(Scalr_Role_Behavior_MongoDB::SERVER_SHARD_INDEX);
			$replicaSetIndex = $server->GetProperty(Scalr_Role_Behavior_MongoDB::SERVER_REPLICA_SET_INDEX);
			
			if (isset($serverStatus[$shardIndex][$replicaSetIndex]))
				continue;
			
			switch ($server->status)
			{
				case SERVER_STATUS::PENDING:
				case SERVER_STATUS::INIT:
				case SERVER_STATUS::PENDING_LAUNCH:
					$serverStatus[$shardIndex][$replicaSetIndex] = 'pending';		
				break;
				
				case SERVER_STATUS::RUNNING:
					$status = $server->GetProperty(Scalr_Role_Behavior_MongoDB::SERVER_NODE_STATUS);
					
					$serverStatus[$shardIndex][$replicaSetIndex] = ($status) ? $status : 'active';
					break;
					
				default:
					$serverStatus[$shardIndex][$replicaSetIndex] = 'terminated';
					break;
			}
		}
		
		for ($i = 0; $i < $dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_SHARDS_COUNT); $i++) {
			for ($j = 0; $j < $dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_REPLICAS_COUNT); $j++) {
				$moduleParams['mongodb']['map'][$i][$j] = (!is_null($serverStatus[$i][$j])) ? $serverStatus[$i][$j] : "terminated";
			}
		}
		$moduleParams['dtLastBundle'] = $dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::DATA_BUNDLE_LAST_TS) ? Scalr_Util_DateTime::convertTz((int)$dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::DATA_BUNDLE_LAST_TS), 'd M Y \a\\t H:i:s') : 'Never';

		$this->response->page('ui/services/mongodb/status.js', $moduleParams, array(), array('ui/services/mongodb/status.css'));
	}
	
	public function xGetClusterLogAction()
	{
		$dbFarmRole = $this->getFarmRole();
		
		$sql = "SELECT id, severity, dtadded, message FROM services_mongodb_cluster_log WHERE farm_roleid=".$this->db->qstr($dbFarmRole->ID);

		$response = $this->buildResponseFromSql($sql, array("message", "severity"), " ORDER BY id DESC");
		foreach ($response["data"] as &$row) {
			$row['dtadded'] = Scalr_Util_DateTime::convertTz($row['dtadded']);
			$row['message'] = nl2br(htmlspecialchars($row['message']));
		}
		
		$this->response->data($response);
	}
	
	public function xTerminateAction()
	{
		$dbFarmRole = $this->getFarmRole();
		
		$cStatus = $dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_CLUSTER_STATUS);
		if ($cStatus && $cStatus != Scalr_Role_Behavior_MongoDB::STATUS_ACTIVE)
			throw new Exception("Shut-down process already initiated");

		foreach ($dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING)) as $server) {
			if ($server->GetProperty(Scalr_Role_Behavior_MongoDB::SERVER_IS_CFG_SERVER)) {
				$cfgServer = $server;
				break;
			}
		}
		
		if (!$cfgServer && $cStatus == Scalr_Role_Behavior_MongoDB::STATUS_ACTIVE)
			throw new Exception("There is no running mongoc-config node. Scalr cannot terminate cluster.");
		elseif ($cfgServer)	
			$sendMsg = $cfgServer->SendMessage(new Scalr_Messaging_Msg_MongoDb_ClusterTerminate());
		//TODO: Handle situation when message was not sent.
		
		$dbFarmRole->SetSetting(Scalr_Role_Behavior_MongoDB::ROLE_CLUSTER_STATUS, Scalr_Role_Behavior_MongoDB::STATUS_SHUTTING_DOWN);
		
		Scalr_Role_Behavior::loadByName(ROLE_BEHAVIORS::MONGODB)->log($dbFarmRole, "Initiated cluster shutdown");
		
		$this->response->success("Cluster shutdown initiated");
	}
	
	public function xRemoveShardAction()
	{
		$dbFarmRole = $this->getFarmRole();
		if ($dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_CLUSTER_STATUS) != Scalr_Role_Behavior_MongoDB::STATUS_ACTIVE)
			throw new Exception("You cannot remove shard from non-active cluster");
		
		$rShard = $dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_CLUSTER_IS_REMOVING_SHARD_INDEX);
		if (is_int($rShard))
			throw new Exception("Shard #{$rShard} is already in removing state. Please wait till this shard will be completely removed.");
		
		
		$shardsCount = $dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_SHARDS_COUNT);
		
		if ($shardsCount == 1)
			throw new Exception("You cannot remove Shard #0. MongoDB should have at least 1 shard.");
		
		$lastShardIndex = $shardsCount-1;
		
		foreach ($dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING)) as $server) {
			if ($server->GetProperty(Scalr_Role_Behavior_MongoDB::SERVER_SHARD_INDEX) == $lastShardIndex) {
				if ($server->GetProperty(Scalr_Role_Behavior_MongoDB::SERVER_IS_ROUTER)) {
					$router = $server;
					break;
				}
			}
		}
		
		if (!$router)
			throw new Exception("Unable to remove shard. No running shard routers found.");
			
		$msg = new Scalr_Messaging_Msg_MongoDb_RemoveShard();
		
		$router->SendMessage($msg);
		
		$dbFarmRole->SetSetting(Scalr_Role_Behavior_MongoDB::ROLE_CLUSTER_IS_REMOVING_SHARD_INDEX, $lastShardIndex);
		
		Scalr_Role_Behavior::loadByName(ROLE_BEHAVIORS::MONGODB)->log($dbFarmRole, "Initiated shard #{$lastShardIndex} removal");
		
		$this->response->success("Shard removal has been initiated. It may take a few minutes before it will be removed.");
	}
	
	public function xAddShardAction()
	{
		$dbFarmRole = $this->getFarmRole();
		if ($dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_CLUSTER_STATUS) != Scalr_Role_Behavior_MongoDB::STATUS_ACTIVE)
			throw new Exception("You cannot add shard to non-active cluster");
		
		$rShard = $dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_CLUSTER_IS_REMOVING_SHARD_INDEX);
		if (is_int($rShard)) {
			throw new Exception("Removing #{$rShard} was initiated. Please wait till shard will be completely removed");
		}
		
		$shardsCount = $dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_SHARDS_COUNT);
		
		$dbFarmRole->SetSetting(Scalr_Role_Behavior_MongoDB::ROLE_SHARDS_COUNT, $shardsCount+1);
		
		Scalr_Role_Behavior::loadByName(ROLE_BEHAVIORS::MONGODB)->log($dbFarmRole, sprintf("Requested new shard. Adding #%s shard to the cluster", $shardsCount));
		
		$this->response->success('Shard successfully added. It may take a few minutes before it becomes available.');
	}
	
	public function xRemoveReplicaSetAction()
	{
		$dbFarmRole = $this->getFarmRole();
		if ($dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_CLUSTER_STATUS) != Scalr_Role_Behavior_MongoDB::STATUS_ACTIVE)
			throw new Exception("You cannot remove replica set from non-active cluster");
		
		$replicasCount = $dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_REPLICAS_COUNT);
		
		if ($replicasCount == 1)
			throw new Exception("You already have minimum amount of replicas. First replica set cannot be removed.");
		
		$rReplica = $replicasCount-1;
			
		$dbFarmRole->SetSetting(Scalr_Role_Behavior_MongoDB::ROLE_REPLICAS_COUNT, $rReplica);
		
		// Terminate instances
		foreach ($dbFarmRole->GetServersByFilter(array('status' => array(SERVER_STATUS::RUNNING, SERVER_STATUS::INIT, SERVER_STATUS::PENDING, SERVER_STATUS::PENDING_LAUNCH))) as $server) {
			if ($server->GetProperty(Scalr_Role_Behavior_MongoDB::SERVER_REPLICA_SET_INDEX) == $rReplica) {
				Scalr::FireEvent($server->farmId, new BeforeHostTerminateEvent($server, false));
			}
		}
		
		Scalr_Role_Behavior::loadByName(ROLE_BEHAVIORS::MONGODB)->log($dbFarmRole, sprintf("Removing replica set #s from cluster", $rReplica));
		
		$this->response->success('Replica removal has been initiated. It may take a few minutes before it will be removed.');
	}
	
	public function xAddReplicaSetAction()
	{
		$dbFarmRole = $this->getFarmRole();
		if ($dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_CLUSTER_STATUS) != Scalr_Role_Behavior_MongoDB::STATUS_ACTIVE)
			throw new Exception("You cannot add replica set from non-active cluster");
		
		$replicasCount = $dbFarmRole->GetSetting(Scalr_Role_Behavior_MongoDB::ROLE_REPLICAS_COUNT);
		
		$dbFarmRole->SetSetting(Scalr_Role_Behavior_MongoDB::ROLE_REPLICAS_COUNT, $replicasCount+1);
		
		Scalr_Role_Behavior::loadByName(ROLE_BEHAVIORS::MONGODB)->log($dbFarmRole, sprintf("Requested new replica set. Adding #%s replica set to the cluster", $replicasCount+1));
		
		$this->response->success('Replica successfully added. It may take a few minutes before it becomes available.');
	}
}
