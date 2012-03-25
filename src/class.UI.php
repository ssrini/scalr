<?
	class UI
	{		
		/**
		 * Display error on screen
		 *
		 * @param unknown_type $message
		 * @param int $code One of PHP's error codes. 
		 */
		public static function DisplayException(Exception $e)
		{
			restore_error_handler();
	 		$code = $e->getCode();
	 		$message = $e->getMessage();
			
			
	 		// Defaultize $code. Not sure if we can place a constant in param default, since constants are kind of late-binded
	 		$code = ($code == null) ? E_USER_ERROR : $code;
	 		
	 		// Generate backtrace if debug mode flag set
	 		// Do not show backtrace for clients. Put it to log only!
 			//if (CONFIG::$DEBUG_APP)
 			//	$bt = Debug::Backtrace($e);
 			
 			// In Application Exception we already add log entry
 			if (!($e instanceof ApplicationException))
 			{
 				// Log exception
 				if (class_exists("Logger"))
 				{
					if (!stristr($message, "Could not connect to host") && 
						!stristr($message, "Error Fetching http headers") &&
						!stristr($message, "DTD are not supported by SOAP"))
 						Logger::getLogger('Exception')->fatal($message);
 					else
 						Logger::getLogger('Exception')->warn($message);
 				}
 			}

		 	// Display
 			switch (CONTEXTS::$APPCONTEXT)
 			{ 	 		    	
 				case APPCONTEXT::AJAX_REQUEST:
 					 
 					if ($e->getCode() == ApplicationException::NOT_AUTHORIZED)	
 					{	 								
	 					header("HTTP/1.1 401 Unauthorized");	 					
 					}
	 				else 
	 				{ 					
	 					header("HTTP/1.1 500 Internal server error");
	 				}		 						
	 				print $e->getMessage();
 					exit(); 
 					
 				case APPCONTEXT::CRONJOB:
	 				die($message);

	 			// Exception in Registrant
	 			case APPCONTEXT::CONTROL_PANEL:
	 				
	 				if (!defined("NO_TEMPLATES"))
		 		    {
		 		    	// Display error
		 				$Smarty = Core::GetSmartyInstance();
		 			    $Smarty->assign(array("backtrace" => "", "message" => $message, "lang" => LOCALE));
		 			    
		 			    $sub_src_dir = realpath(dirname($_SERVER["SCRIPT_FILENAME"]). "/src");
		 			    
		 			    //
						// Load menu
						//
						//Core::load("XMLNavigation", $sub_src_dir);
						require("{$sub_src_dir}/navigation.inc.php");
						
						$post_serialized = self::SerializePOST($_POST);
						
						$Smarty->assign(array(
							"dmenu" => $XMLNav->DMenu,
							"post_serialized" => $post_serialized,
							"get_url" => $_SERVER['REQUEST_URI'],
							)
						);
					    $Smarty->display("exception_cp.tpl");
		 		    }
	 				break;
	 					 			
	 			// Default to show regular exception
	 			default:
	 				
 					if (!defined("NO_TEMPLATES"))
		 		    {
		 		    	// Display error
		 				$Smarty = Core::GetSmartyInstance();
		 			    $Smarty->assign(array("backtrace" => "", "message" => $message, "lang" => LOCALE));
					    $Smarty->display("exception.tpl");
		 		    }
	 				break;
 			}
 			exit();
		}
		
		
		/**
		 * Simple post serialization
		 *
		 * @return string a mix of hidden fileds, ready to be embedded to HTML form as-is.
		 */
		private function SerializePOST()
		{
			if ($_POST)
			{
				foreach($_POST as $k=>$v)
				{
					if (is_array($v))
					{
						foreach($v as $vv)
						{
							$retval .= "<input type=hidden name='{$k}[]' value='{$vv}'>";
						}
					}
					else
						$retval .= "<input type=hidden name='{$k}' value='{$v}'>";
				}
			}
			return $retval;
		}
		
		
		/**
		 * Redirects page to $url
		 *
		 * @param string $url
		 * @static 
		 */
		public static function Redirect($url)
		{
			if (!$_SESSION["mess"])
				$_SESSION["mess"] = $GLOBALS["mess"];
				
			if (!$_SESSION["okmsg"])
				$_SESSION["okmsg"] = $GLOBALS["okmsg"];
				
			if (!$_SESSION["errmsg"])
				$_SESSION["errmsg"] = $GLOBALS["errmsg"];
			
			if (!$_SESSION["warnmsg"])
				$_SESSION["warnmsg"] = $GLOBALS["warnmsg"];
				
			if (!$_SESSION["err"])
				$_SESSION["err"] = $GLOBALS["err"];
			
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
			{
				echo "
				<script type='text/javascript'>
				<!--
				document.location.href='{$url}';
				-->
				</script>
				<meta http-equiv='refresh' content='0;url={$url}'>
				";
	  			exit();
	  			
			} 
			else 
			{
				header("Location: {$url}");
				exit();
			}
		}
		
		/**
		 * Redirect parent URL
		 *
		 * @param string $url
		 * @static 
		 */
		public static function RedirectParent($url)
		{
			echo "
			<script type='text/javascript'>
			<!--
				parent.location.href='{$url}';
			-->
			</script>";
  			die();
		}
		
		/**
		* Submit HTTP post to $url with form fields $fields
		* @access public
		* @param string $url URL to redirect to
		* @param string $fields Form fields
		* @return void
		* @static 
		*/
		public static function RedirectPOST($url, $fields)
		{
			$form = "
			<html>
			<head>
			<script type='text/javascript'>
			function MM_findObj(n, d) { //v4.01
			  var p,i,x;  if(!d) d=document; if((p=n.indexOf('?'))>0&&parent.frames.length) {
				d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
			  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
			  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
			  if(!x && d.getElementById) x=d.getElementById(n); return x;
			}
			</script>
			</head>
			<body>
			<form name='form1' method='post' action='$url'>";
			foreach ($fields as $fk=>$fv)
				$form .= "<input type='hidden' id='$fk' name='$fk' value='$fv'>";
			$form .= "</form>
			<script type='text/javascript'>
			MM_findObj('form1').submit();
			</script>
			</body>
			</html>
			";
			
			die($form);
		}
	}
?>