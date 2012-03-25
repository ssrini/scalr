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
     * @package    UI
     * @subpackage Paging
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */
    
    /**
	 * @name       SQLPaging
	 * @category   LibWebta
     * @package    UI
     * @subpackage Paging
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 * @author Igor Sacvhenko <http://webta.net>
	 */
    class SQLPaging extends Paging
    {
		
		public $SQL;
		public $AdditionalSQL;
		
		private $SQLExecuted;
		
		/**
		* Either we have already added LIMIT to SQL query or not
		* @var bool $PagingApplied
		* @access public
		*/
		protected $PagingApplied;
		
		/**
		 * SQL Paging
		 *
		 * @param string $sql SQL Query without LIMIT x,x
		 * @param integer $page Current page
		 * @param integer $pagesize Page size
		 */
		function __construct($sql=null, $page=null, $pagesize=null)
        {
			parent::__construct($page, 0, $pagesize);
			$this->DB = Core::GetDBInstance();
			
			if ($sql)
                $this->SetSQLQuery($sql);
        }
        
        /**
         * Set SQL Query
         *
         * @param string $sql
         */
        function SetSQLQuery($sql)
        {
            $this->SQL = $sql;
			$this->SQLExecuted = false;
        }
        
        /**
		* Return SQL with applied paging limits
		* @access public
		* @return string Usable SQL string
		*/
        public function ApplySQLPaging()
        {
        	if (!stristr($this->SQL, $this->AdditionalSQL))
				$this->SQL .= " ".$this->AdditionalSQL." ";
			
			if (!$this->SQLExecuted)
				$this->ExecuteSQL();
		
			$this->SQL = sprintf("%s LIMIT %d, %d", $this->SQL, $this->GetOffset(), $this->ItemsOnPage);
			$this->PagingApplied = true;
		
			return($this->SQL);
		}
			
		/**
		* Execute current SQL and count Total rows
		* @access private
		* @return bool;
		*/
		private function ExecuteSQL()
		{
			$rs = $this->DB->Execute($this->SQL);
			$this->Total = $rs->RecordCount();			
			$this->SQLExecuted = true;
			return true;
		}
		
		/**
		* Adds WHERE filter to SQL, depending on SESSION and POST filters.
		* @access public
		* @return string Usable SQL string
		*/
		public function ApplyFilter($filter, $fields, $subquery = false)
		{			
			if ($this->PagingApplied)
				Core::RaiseError(_("Cannot call ApplyFilter after ApplySQLPaging has been called"));

		    $filter = stripslashes($filter);
						
			// Same filter - unchecking button
			if ($filter == $_SESSION["filter"])
			{
				$filter = NULL;
				$_SESSION["filter"] = NULL;
				$this->Display["filter"] = false;
			}
			else
			{
				if (!$_GET["pf"])
					$this->PageNo = 1;
			
				$filter = $filter ? $filter : $_GET["pf"];
				$filter = stripslashes($filter);
				
				// Add template vars
				$this->Display["filter_q"] = $filter;
				$this->Display["filter"] = true;
				$_SESSION["filter"] = $filter;
			}
			
			if ($filter)
			{
				$this->URLFormat = "?pn=%d&pt=%d&pf=" . urlencode($filter);
				$this->Filter = $filter;
				
				
				//SQL
				$filter = mysql_escape_string($filter);
				foreach($fields as $f)
					$likes[] = "$f LIKE '%{$filter}%'";
					
				if ($subquery)
					$likes[] = str_replace("[FILTER]", $filter, $subquery);
					
				$like = implode(" OR ", $likes);
				
				if (!stristr($this->SQL, "WHERE"))
					$this->SQL .= " WHERE {$like}";
				else
					$this->SQL .= " AND ($like)";
				
										
				
				// Additional SQL
				if (!stristr($this->SQL, $this->AdditionalSQL))
					$this->SQL .= " ".$this->AdditionalSQL." ";
					
				// Downgrade total records count
				if ((int)$_GET["pt"] > 0)
					$this->Total = (int)$_GET["pt"];
				else
					$this->ExecuteSQL();

			}
			
			return $this->SQL;
		}
		
		function ParseHTML()
		{
			parent::ParseHTML();
		}
		
		function GetPagerHTML($template = "paging.tpl")
		{
			return (parent::GetHTML($template));
		}
		
	}

?>