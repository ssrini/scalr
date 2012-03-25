<?
    /**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
	 * This source file is subject to version 2 of the GPL license,
	 * that is bundled with this package in the file license.txt and is
	 * available through the world-wide-web at the following url:
	 * http://www.gnu.org/copyleft/gpl.html
     *
     * @category   LibWebta
     * @package    IO
     * @subpackage Transports
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */
    
    /**
     * Transport Interface
     * 
     * @name       ITransport
     * @category   LibWebta
     * @package    IO
     * @subpackage Transports
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	interface ITransport
	{
	    public function Read($filename);
	    
	    public function Write($filename, $content, $overwrite = true);
	    
	    public function Copy($old_path, $new_path, $recursive = true);
	    
	    public function Remove($path, $recursive = true);
	    
	    public function Chmod($path, $perms, $recursive = true);
	    
	    public function Move($old_path, $new_path, $recursive = true);
	    
	    public function MkDir($path);
	    
	    public function Execute($command);
	}
?>