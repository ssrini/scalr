<?php


/**
 * Array utils
 * 
 * @author Marat Komarov
 */
class Scalr_Util_Arrays {

	/**
	 * @see http://ua2.php.net/manual/en/function.array-merge-recursive.php#93905
	 * @return array
	 */
	function mergeReplaceRecursive() {
	    // Holds all the arrays passed
	    $params = &func_get_args();
	   
	    // First array is used as the base, everything else overwrites on it
	    $return = array_shift ($params);
	   
	    // Merge all arrays on the first array
	    foreach ($params as $array) {
	        foreach ($array as $key => $value) {
	            // Numeric keyed values are added (unless already there)
	            if (is_numeric($key) && (!in_array($value, $return))) {
	                if (is_array ($value )) {
	                    $return [] = self::mergeReplaceRecursive ($return [$key], $value);
	                } else {
	                    $return [] = $value;
	                }
	               
	            // String keyed values are replaced
	            } else {
	                if (isset ($return[$key]) && is_array ($value) && is_array ($return [$key])) {
	                    $return[$key] = self::mergeReplaceRecursive($return[$key], $value);
	                } else {
	                    $return[$key] = $value;
	                }
	            }
	        }
	    }
	   
	    return $return;
	}	
}