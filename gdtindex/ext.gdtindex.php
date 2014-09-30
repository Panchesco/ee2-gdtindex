<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *	Gdtindex_ext
 *
 *	@package		Gdtindex
 *	@author			Richard Whitmer/Godat Design, Inc.
 *	@copyright	(c) 2014, Godat Design, Inc.
 *	
 *	@link				http://godatdesign.com
 *	@since			Version 1.0
 */
 
 // ------------------------------------------------------------------------

class Gdtindex_ext {

    public	$settings       =	array('channel_id'=>array());
    public	$name       		= 'Good at Index';
    public	$version        = '1.0';
    public	$description    = 'Handler for Good at Index';
    public	$settings_exist = 'y';
    public	$docs_url       = ''; // 'https://ellislab.com/expressionengine/user-guide/';
    public	$entry_id				= 0;
    public	$member_id			= 0;
    public	$multis					= array('multi_select','checkboxes');
    public	$multi_selects	= array();
    public	$field_data			= array();
    public	$field_ids			= array();
    public	$field_names		= array();

	    /**
	     * Constructor
	     *
	     * @param   mixed   Settings array or empty string if none exist.
	     */
	    function __construct($settings='')
	    {
	    		// Make a local reference to the ExpressionEngine super object
					$this->EE =& get_instance();
					
					ee()->load->add_package_path(PATH_THIRD.'gdtindex/');
					
					ee()->load->library('shared');

	    }

   /**
		* Activate Extension
		*
		* This function enters the extension into the exp_extensions table
		*
		* @see https://ellislab.com/codeigniter/user-guide/database/index.html for
		* more information on the db class.
		*
		* @return void
		*/
		function activate_extension()
		{

				$this->settings	= ee()->shared->settings;
		
			   // Register hooks.
			   $hooks	= array('entry_submission_end'	=> 'index_entry',
			   					'delete_entries_start'	=>	'delete_batch');
			   					
			   foreach($hooks as $hook => $method)
			   {
					   $data = array(
				       'class'     => __CLASS__,
				       'method'    => $method,
				       'hook'      => $hook,
				       'settings'  => serialize($this->settings),
				       'priority'  => 10,
				       'version'   => $this->version,
				       'enabled'   => 'y'
				   );
				   
				   ee()->db->insert('extensions', $data);
			   }

		 }
		 
		 
		 /**
		 * Update Extension
		 *
		 * This function performs any necessary db updates when the extension
		 * page is visited
		 *
		 * @return  mixed   void on update / false if none
		 */
		public function update_extension($current = '')
		{
		    if ($current == '' OR $current == $this->version)
		    {
		        return FALSE;
		    }
		
		    if ($current < '1.0')
		    {
		        // Update to version 1.0
		    }
		
		    ee()->db->where('class', __CLASS__);
		    ee()->db->update(
		                'extensions',
		                array('version' => $this->version)
		    );
		}
		
		
		/**
		 * Disable Extension
		 *
		 * This method removes information from the exp_extensions table
		 *
		 * @return void
		 */
		public function disable_extension()
		{
		    ee()->db->where('class', __CLASS__);
		    ee()->db->delete('extensions');
		}
		
	// ----------------------------------------------------------------------
	
	/**
	 * Handling indexing for new and updated entries.
	 * @return boolean
	 */
	public function index_entry()
	{
		// Add Code for the extension hook here.
		
		// Load shared library.
	//ee()->load->library('shared');
		
		// Set field_data.
		$this->field_data		= ee()->shared->channel_field_data();
		
		// Set the field array.
		//	$this->set_field_arrays();
		
		// Set multi_selects
		//$this->set_multi_selects();
		
  
	     if( ee()->input->post('entry_id') == 0 )
		{
				$entry_id	= ee()->shared->last_entry_id();
			
			} else {
				
				$entry_id	= ee()->input->post('entry_id');
		}
		
		// Update options for this entry;
		$update = ee()->shared->update_entry_index($entry_id);

		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	
	/**
	 *	Build index table.
	 *	@return boolean
	 */
	 public function build_index()
	 {
	 		
	 		$this->EE->load->dbforge();
	 		
	 		$this->EE->dbforge->drop_table(ee()->shared->index_table);
	 		
	 		$fields = array(
			
			'channel_id'	=> array('type' => 'int',
								'constraint'	=> '10',
								'unsigned'	=> TRUE,
								'null'	=> FALSE),			
			
			'entry_id'	=> array('type' => 'int',
								'constraint'	=> '10',
								'unsigned'	=> TRUE,
								'null'	=> FALSE),
								

								
			'entry_text'	=> array('type'=>'text','null'=>TRUE),
			
			'field_id'		=> array('type'=>'varchar',
									'constraint'	=> '19',
									'null' => FALSE),
									
			'field_value'	=> array('type'=>'varchar',
									'constraint' => '250',
									'null' => FALSE),
		);
		
		
		$this->EE->dbforge->add_field($fields);
		
		return $this->EE->dbforge->create_table(ee()->shared->index_table,TRUE);
		

	 }
	 
	 
	// --------------------------------------------------------------------
	
	
	public function index_fields()
	{
		
				$sql = "CREATE INDEX entry_id_index ON " . ee()->db->dbprefix . ee()->shared->index_table . "(entry_id)";
				ee()->db->query($sql); 
			
				$sql = "CREATE INDEX field_id_index ON " . ee()->db->dbprefix . ee()->shared->index_table . "(field_id)";
				ee()->db->query($sql); 
			 
				$sql = "CREATE INDEX field_value_index ON " . ee()->db->dbprefix . ee()->shared->index_table . "(field_value)";
				ee()->db->query($sql); 
				
				return TRUE;
	}
	 

	
	
	/**
	 *	Populate options table.
	 */
	 public function populate_index()
	 {
	 
	 			$entry_ids	= ee()->shared->entry_ids();
	    	
	    	foreach($entry_ids as $entry_id)
	    	{
		    	ee()->shared->update_entry_index($entry_id);
	    	}
	    	
		 
	 }
	 
	
	// --------------------------------------------------------------------
	
	/**
	 * Delete a batch of index entries.
	 * @return boolean
	 */
	 public function delete_batch()
	 {
		 
		 	$ids	= ee()->input->post('delete');
		 
		 	$delete_options	= ee()->db->where_in('entry_id',$ids)->delete(ee()->shared->index_table);
		 	//$delete_idices		= ee()->db->where_in('entry_id',$ids)->delete(ee()->shared->index_table);
		 
		 return TRUE;
	 }  
	  
	  
	  
	 // --------------------------------------------------------------------
	  
 
	 /**
	  * Settings Form
	  *
	  * @param   Array   Settings
	  * @return  void
	  */
	 function settings_form($current)
	 {
	     $channels		= array();
	     
	 
	     $this->settings	= ee()->shared->settings;
	     
	     ee()->load->library('api'); 
	     ee()->api->instantiate('channel_structure');
	     
	     $api_result	= ee()->api_channel_structure->get_channels()->result();
	     foreach($api_result as $key=>$row)
	     {
	 	    $channels[$row->channel_id]		= $row->channel_title;
	     }
	     
	     ee()->load->helper('form');
	     ee()->load->library('table');
	 
	     $vars = array();
	 
	     $channel_id	= (isset($this->settings['channel_id'])) ? $this->settings['channel_id'] : array(); 
	     $vars['settings']	= array();
	     $checkboxes = '';
	     
	     foreach($channels as $id => $title)
	     {
	     	$checkboxes.= form_checkbox('channel_id[]',$id,((in_array($id,$channel_id)) ? TRUE : FALSE)) . ' ' . $title.'<br>';
	     }
	     
	     $vars['settings'] = array('Channels to include in index'=>$checkboxes);
	 
	     return ee()->load->view('index', $vars, TRUE);
	 }



	 /**
	  * Save Settings
	  *
	  * This function provides a little extra processing and validation
	  * than the generic settings form.
	  *
	  * @return void
	  */
	 function save_settings()
	 {
	     if (empty($_POST))
	     {
	         show_error(lang('unauthorized_access'));
	     }
	 
	     unset($_POST['submit']);
	 
	     ee()->lang->loadfile('gdtindex');
	 
	     $len = ee()->input->post('channel_id');
	     
	     foreach($this->settings as $key=>$row)
	     {
	 	    $this->settings[$key] = ee()->input->post($key);
	     }
	 
	     ee()->db->where('class', __CLASS__);
	     ee()->db->update('extensions', array('settings' => serialize($this->settings)));
	 
	     ee()->session->set_flashdata(
	         'message_success',
	         lang('preferences_updated')
	 
	     );
	     
	     	// Since we're updating the channel_id data, we'll need to initialize the Shared class properties.
			 	ee()->shared->initialize();

	      
	      // Build and populate the options table based on the current settings.
	      if($this->build_index())
	      {
		      
		      // Seed the table from existing entries.
		      $this->populate_index();
		      
		      // Create db table indexes
		      $this->index_fields();
		      
	      
	      };

	 }


    
}
// END CLASS

/* End of file ext.gdtindex.php */
/* Location: ./system/expressionengine/third_party/gdtindex/ext.gdtindex.php */