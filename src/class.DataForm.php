<?
	class DataForm
	{
		/**
		 * Form fields. Array of DataForm objects.
		 *
		 * @var array
		 */
		protected $Fields;
		
		/**
		 * Inline help that will appear in yellow box above the form, if Inline help is enabled in registrar/registant CP.
		 *
		 * @var string
		 */
		protected $InlineHelp; 
		
		/**
		 * Returns Inline help that will appear in yellow box above the form, if Inline help is enabled in registrar/registant CP.
		 *
		 * @return unknown
		 */
		public function GetInlineHelp()
		{
			return $this->InlineHelp;
		}
		
		/**
		 * Set Inline help that will appear in yellow box above the form, if Inline help is enabled in registrar/registant CP.
		 *
		 * @param string $inline_help
		 */
		public function SetInlineHelp($inline_help)
		{
			$this->InlineHelp = $inline_help;
		}
		
		/**
		 * Loads DataForm From JSON string
		 *
		 * @param string $json_dataform
		 */
		public function LoadFromJSON($json_dataform)
		{
			$data_form = json_decode($json_dataform);
		}
		
		/**
		 * Append form field.
		 * @param FormField
		 */
		public function AppendField(DataFormField $field)
		{
			if ($field instanceof DataFormField)
				$this->Fields[$field->Name] = $field;
			else 
				throw new Exception(_("Field must be an instance of DataFormField"));
		}
		
		/**
		 * Returns array of form fields (DataFormField objects); 
		 * @return array
		 */
		public function ListFields()
		{
			return $this->Fields;
		}
		
		/**
		 * Clear fields
		 *
		 */
		public function ClearFields()
		{
			$this->Fields = array();
		}
		
		/**
		 * Return field object by name
		 * @return DataFormField
		 */
		public function GetFieldByName($name)
		{
			return (isset($this->Fields[$name])) ? $this->Fields[$name] : null;
		}
	}

?>