<?php

class Scalr_UI_Controller_Tools_Aws_Rds_Pg extends Scalr_UI_Controller
{
	public function viewAction()
	{
		$this->response->page('ui/tools/aws/rds/pg/view.js', array(
			'locations'	=> self::loadController('Platforms')->getCloudLocations(SERVER_PLATFORMS::EC2, false)
		));
	}

	public function xListAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
		
		// Rows		
		$dbParameterGroups = $amazonRDSClient->DescribeDBParameterGroups();
		$groups = (array) $dbParameterGroups->DescribeDBParameterGroupsResult->DBParameterGroups;
		$groups = $groups['DBParameterGroup'];

		if ($groups) {
			if (!is_array($groups))
				$groups = array($groups);
		}
		
		$response = $this->buildResponseFromData($groups, array('DBParameterGroupDescription', 'DBParameterGroupName'));
		
		$this->response->data($response);
	}
	
	public function xCreateAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
		
		$amazonRDSClient->CreateDBParameterGroup($this->getParam('dbParameterGroupName'), $this->getParam('Description'), $this->getParam('Engine'));
		
		$this->response->success("DB parameter group successfully created");
	}
	
	public function xDeleteAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
			
		$amazonRDSClient->DeleteDBParameterGroup($this->getParam('name'));
		$this->response->success("DB parameter group successfully removed");
	}
	
	public function editAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
		
		$response = $amazonRDSClient->DescribeDBParameters($this->getParam('name'));	
		$result = json_decode(json_encode($response->DescribeDBParametersResult->Parameters), true);
		$params = $result['Parameter'];
		$dbParameterGroups = $amazonRDSClient->DescribeDBParameterGroups($this->getParam('name'));
		$groups = (array) $dbParameterGroups->DescribeDBParameterGroupsResult->DBParameterGroups;
		$groups = $groups['DBParameterGroup'];
		
		$items = array();
		foreach ($params as $key => $value) {
			$itemField = new stdClass();
			if(strpos($value['AllowedValues'], ',') && $value['DataType'] != 'boolean')
			{
					$store = explode(',', $value['AllowedValues']);
					$itemField->xtype = 'combo';
					$itemField->allowBlank = true;			
					$itemField->editable = false;
					$itemField->queryMode = 'local';
					$itemField->displayField = 'name';
					$itemField->valueField = 'name';
					$itemField->store = $store;
			}
			else if($value['DataType'] == 'boolean')
			{
				$itemField->xtype = 'checkbox';
				$itemField->inputValue = 1;
				$itemField->checked = ($value['ParameterValue'] == 1);
			}
			else {
				if($value['IsModifiable'] == "false")
					$itemField->xtype = 'displayfield';
				else 
					$itemField->xtype = 'textfield';
			}
			$itemField->name = $value['Source'] . '[' . $value['ParameterName'] . ']';
			$itemField->fieldLabel = $value['ParameterName'];
			$itemField->value = $value['ParameterValue'];
			$itemField->labelWidth = 250;
			$itemField->width = 820;
			$itemField->readOnly = ($value['IsModifiable'] == "false" && $itemField->xtype != 'displayfield') ? true : false;
			
			
			$itemDesc = new stdClass();
			$itemDesc->xtype = 'displayfield';
			$itemDesc->width = 16;
			$itemDesc->margin = new stdClass();
			$itemDesc->margin->left = 5;
			$itemDesc->value = '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">';
			$itemDesc->hText = $value['Description'];
			
			
			$item = new stdClass();
			$item->xtype = 'fieldcontainer';
			$item->layout = 'hbox';
			$item->items = array(
				$itemField,
				$itemDesc
			);
			
			$items[$value['Source']][] = $item;
		}
		$this->response->page('ui/tools/aws/rds/pg/edit.js', array('params' => $items, 'group' => $groups));
	}
	
	public function xSaveAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
		
		$response = $amazonRDSClient->DescribeDBParameters($this->getParam('name'));	
		$result = json_decode(json_encode($response->DescribeDBParametersResult->Parameters), true);
		$params = $result['Parameter'];
		
		$modifiedParameters = new ParametersList();
		$newParams = array();
		
		foreach($this->getParam('system') as $system=>$f) {
			$newParams[] = array(
					'ParameterName' => $system,
					'ParameterValue' => $f
			);
		}
		foreach($this->getParam('engine-default') as $default=>$f) {
			$newParams[] = array(
					'ParameterName' => $default,
					'ParameterValue' => $f
			);
		}
		foreach($this->getParam('user') as $user=>$f) {
			$newParams[] = array(
					'ParameterName' => $user,
					'ParameterValue' => $f
			);
		}
		foreach ($newParams as $newParam){
			foreach($params as $param) {
				if($param['ParameterName'] == $newParam['ParameterName']){
					if(
						(empty($param['ParameterValue']) && !empty($newParam['ParameterValue'])) || 
						(!empty($param['ParameterValue']) && empty($newParam['ParameterValue'])) ||
						($newParam['ParameterValue'] !== $param['ParameterValue'] && !empty($newParam['ParameterValue']) && !empty($param['ParameterValue']))
					){
						if($param['ApplyType'] == 'static')				
							$modifiedParameters->AddParameters($newParam['ParameterName'],$newParam['ParameterValue'],"pending-reboot");								
						else									
							$modifiedParameters->AddParameters($newParam['ParameterName'],$newParam['ParameterValue'],"immediate");
					}
				}
			}
		}
		$oldBoolean = array();
		foreach ($params as $param) {
			if($param['DataType'] == 'boolean' && $param['ParameterValue'] == 1){
				$type = '';
				if($param['ApplyType'] == 'static')				
					$type = "pending-reboot";								
				else									
					$type = "immediate";
				$oldBoolean[] = array(
						'ParameterName' => $param['ParameterName'],
						'ParameterValue' => $param['ParameterValue'],
						'ApplyType' => $type
				);
			}
		}
		foreach ($oldBoolean as $old){
			$found = false;
			foreach ($newParams as $newParam){
				if($old['ParameterName'] == $newParam['ParameterName'])
					$found = true;
			}
			if(!$found){
				$modifiedParameters->AddParameters($old['ParameterName'], 0, $old['ApplyType']);
			}
		}
		//$this->response->data( array('oldboolean' => $oldBoolean));
		$amazonRDSClient->ModifyDBParameterGroup($this->getParam('name'),$modifiedParameters);
		$this->response->success("DB parameter group successfully updated");
	}

	public function xResetAction() 
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
		
		$response = $amazonRDSClient->DescribeDBParameters($this->getParam('name'));	
		$result = json_decode(json_encode($response->DescribeDBParametersResult->Parameters), true);
		$params = $result['Parameter'];
		
		$modifiedParameters = new ParametersList();
		foreach ($params as $param){
			if($param['ParameterValue'] && !empty($param['ParameterValue'])){
				if($param['ApplyType'] == 'static')
					$modifiedParameters->AddParameters($param['ParameterName'], $param['ParameterValue'], "pending-reboot");
				else 
					$modifiedParameters->AddParameters($param['ParameterName'], $param['ParameterValue'], "immediate");
			}
		}
		$amazonRDSClient->ResetDBParameterGroup($this->getParam('name'), $modifiedParameters);
		$this->response->success("DB parameter group successfully reset to default");
	}
}