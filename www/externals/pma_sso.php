<?php

	define('PMA_KEY', '!80uy98hH&)#0gsg695^39gsvt7s853r%#dfscvJKGSG67gVB@');
	define('KEY_SAULT', md5($_REQUEST['s']));
	
	session_set_cookie_params(0, '/', '', 0);
	session_name('SignonSession');
	session_start();
	
	if (!$_REQUEST['r'] && $_REQUEST['phpMyAdmin'])
    {
		header("Location: https://scalr.net/externals/pma_redirect.php?c=1&f={$_SESSION['f']}");
		exit();
    }
	
	if (md5($_REQUEST['r'].$_REQUEST['s'].PMA_KEY) != $_REQUEST['h'])
	{
		header("Location: https://scalr.net/externals/pma_redirect.php?c=2&f={$_SESSION['f']}");
		exit();
	}
	else
	{
		$data = pma_decrypt($_REQUEST['r']);
		if (!$data)
		{
			header("Location: https://scalr.net/externals/pma_redirect.php?c=2&f={$_SESSION['f']}");
			exit();
		}
		else
			$pma_auth = unserialize($data);
	}
	
	if($pma_auth) 
	{
		$_SESSION['PMA_single_signon_user'] = $pma_auth['user'];
		$_SESSION['PMA_single_signon_password'] = $pma_auth['password'];
		$_SESSION['PMA_single_signon_host'] = $pma_auth['host'];
		$_SESSION['f'] = $_REQUEST['f'];
		
		session_write_close();
		
		header('Location: ../index.php?server=1');
	}
	
	function pma_decrypt($input)
	{							
		$inputd = base64_decode($input);
		try
		{
			$td = mcrypt_module_open(MCRYPT_3DES, '', 'ecb', '');
			$key = substr(substr(KEY_SAULT, 5).PMA_KEY, 0, mcrypt_enc_get_key_size($td));
			$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			mcrypt_generic_init($td, $key, $iv);
			$retval = mdecrypt_generic($td, $inputd);
			
			mcrypt_generic_deinit($td);
			mcrypt_module_close($td);
		}
		catch (Exception $e)
		{
			return false;
		}
		return trim($retval, "\x00..\x1F");
	}
?>