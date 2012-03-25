<?
	abstract class ScalrEnvironment extends ScalrRESTService
    {    	   	
    	const LATEST_VERSION = '2009-03-05';
    	
    	/**
    	 * 
    	 * @var DBServer
    	 */
    	protected $DBServer;
    	
    	/**
    	 * Query Environment object and return result;
    	 */
    	public function Query($operation, array $args)
		{			
			$this->SetRequest($args);	
    	  	
			// Get Method name by operation
			$method_name = str_replace(" ", "", ucwords(str_replace("-", " ", $operation)));
			
			// Check method
			if (method_exists($this, $method_name))
			{				
				// Call method
				try
				{
					$this->DBServer = $this->GetCallingInstance();
					
					$result = call_user_func(array($this, $method_name));
					if ($result instanceof DOMDocument)
					{
						return $result->saveXML();
					}
					else
						throw new Exception(sprintf("%s:%s() returns invalid response. DOMDocument expected.",
							get_class($this),
							$method_name
						));
				}
				catch(Exception $e)
				{
					throw new Exception(sprintf(_("Cannot retrieve environment by operation '%s': %s"), 
						$operation, 
						$e->getMessage()
					));
				}
			}
			else
				throw new Exception(sprintf(_("Operation '%s' not supported"), $operation));
		}
    	  		
		/**
		 * Create Base DOMDocument for response
		 * @return DOMDocument
		 */
		protected function CreateResponse()
		{
			$DOMDocument = new DOMDocument('1.0', 'utf-8');
			$DOMDocument->loadXML('<response></response>');
			
			return $DOMDocument;
		}
    }
?>