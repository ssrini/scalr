<?php

/**
 * PHP Compatibility functions
 * 
 * @author Marat Komarov
 */
class Scalr_Util_Compat {
	
	function parseIniString ($ini, $process_sections = false, $scanner_mode = null) {
		// @since 5.3.0 
		if (!function_exists('parse_ini_string')) {
			// Generate a temporary file.
			$tempname = tempnam(sys_get_temp_dir(), 'ini');
			file_put_contents($tempname, $ini);
			$ini = parse_ini_file($tempname, !empty($process_sections));
			@unlink($tempname);
			return $ini;			
		}
		return parse_ini_string($ini, $process_sections, $scanner_mode);
	}
}