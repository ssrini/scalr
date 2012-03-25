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
     * @subpackage PCNTL
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */

    /**
     * @name Sample Proccess Object
     * @category Libwebta
     * @package IO
     * @subpackage PCNTL
     * @copyright Copyright (c) 2003-2007 Webta Inc, http://webta.net/copyright.html
     * @license http://webta.net/license.html
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
    class testProcess implements IProcess
    {
        public $ProcessDescription = "Test process for test job";
        
        /**
         * Thread arguments
         *
         * @var array
         */
        public $ThreadArgs;
        
       
        /**
         * In this function we must create $this->ThreadArgs array. One element of this array = one thread
         *
         */
        function OnStartForking()
        {
            $this->ThreadArgs = array(1,2,3,4,5,6,7,8,9,10);
        }
        
        /**
         * What we do aftrer threading
         *
         */
        function OnEndForking()
        {
            //
        }
        
        /**
         * What we do in thread
         *
         * @param mixed $args
         */
        public function StartThread($args)
        {
            sleep(5);
            var_dump($args);
            exit();
        }
    }
?>