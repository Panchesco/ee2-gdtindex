<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *	Shared
 *
 *	@package		Gdtindex
 *	@author			Richard Whitmer/Godat Design, Inc.
 *	@copyright	(c) 2014, Godat Design, Inc.
 *	
 *	@link				http://godatdesign.com
 *	@since			Version 1.0
 */
 
 // ------------------------------------------------------------------------

if( ! class_exists('Shared'))
{

	class Shared 	{
	
		public	$settings					= array('channel_id'=>array());	
		public	$index_table			= 'gdtindex';		
		public	$package_name			= 'Gdtindex';
		public	$member_id				= FALSE;
		public	$channel_ids			= array();
		public	$channel_names		= array();
		public	$field_groups			= array();
		public	$field_data				= array();
		public	$field_ids				= array();
		public	$field_names			= array();
		public	$multis						= array('multi_select','checkboxes');	// field types that accept multiple values.
		public	$multi_selects		= array();
		public	$ignore_fields		= array('entry_id','site_id','channel_id','title','url_title','author_id');
		public	$ignore_field_types	= array('file','grid','relationship');

		function __construct()
		{
			// Make a local reference to the ExpressionEngine super object
			//$this->EE =& get_instance();
			
			$this->initialize();

		}
		
	
	// --------------------------------------------------------------------
	
	/**
	 *	Initalize class with current properties.
	 *	@return void.
	 */
	public function initialize()
	{
		
			$this->member_id = ee()->session->userdata('member_id');
			
			$this->settings		= $this->settings();
			$this->field_data	= $this->channel_field_data();
			$this->set_channel_arrays();
			$this->set_field_arrays();
			$this->set_multi_selects();
			
	}
	
	
	// --------------------------------------------------------------------
	
	public function set_channel_arrays()
	{
		
		ee()->load->library('api'); 
		ee()->api->instantiate('channel_structure');
		$channels	= 	ee()->api_channel_structure->get_channels(FALSE);
		
		foreach($channels->result() as $key=>$row)
		{
			$this->channel_ids[$row->channel_name]	= $row->channel_id;
			$this->channel_names[$row->channel_id]	= $row->channel_name;
			$this->field_groups[$row->channel_id]		= $row->field_group;
		}		
		
	}
	
	// --------------------------------------------------------------------

		
		/** 
		 * Get the extension settings.
		 * @return array
		 */
		 public function settings()
		 {
		
			 // Get settings.
			 // Be certain channel_id is array.
			 $query	= ee()->db
					->select('settings')
					->where('class',$this->package_name.'_ext')
					->limit(1)
					->get('extensions');
					
					if($query->num_rows()>0)
					{
						$this->settings = unserialize($query->row()->settings);
						
						if( isset($this->settings['channel_id']) && ! is_array($this->settings['channel_id']))
						{
							$this->settings['channel_id'] = array();
						} 
						
					} 
		
			return $this->settings;
			
		 }
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Get the last entry_id for current user.
	 * @return integer
	 */
	 function last_entry_id()
	 {
		 $query = ee()->db
		 			->select('entry_id')
		 			->where('author_id',$this->member_id)
		 			->where_in('channel_id',$this->settings['channel_id'])
		 			->limit(1)
		 			->order_by('entry_id','DESC')
		 			->get('channel_titles');

		 
		 if($query->num_rows()==1)
		 {
			return $query->row()->entry_id; 
		 } else {
			 return 0;
		 }
		 
	 }
	 
	 // --------------------------------------------------------------------
	 
	 
	 /**
	  *	Fetch an entry.
	  *	@param entry_id
	  *	@return array
	  */
	  public function entry($entry_id=0)
	  {
	  		// Create the select array.
	  		
	  				$sel = array(
						'channel_titles.entry_id',
						'channel_titles.site_id',
						'channel_titles.channel_id',
						'channel_titles.author_id',
						'channel_titles.title',
						'channel_titles.url_title',
						'channel_titles.status',
						'channel_titles.entry_date',
						'channel_titles.year',
						'channel_titles.month',
						'channel_titles.day');
						
		foreach($this->field_names as $field=>$name)
		{
			$sel[]	= 'channel_data.'.$field;
		}
		
		
		return ee()->db
				->select($sel)
				->where('channel_titles.entry_id',$entry_id)
				->join('channel_data','channel_data.entry_id=channel_titles.entry_id')
				->limit(1)
				->get('channel_titles')
				->row_array();
	  }
	  
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Get data about current channel's field group custom fields.
	 * @return array
	 */
	 public function channel_field_data()
	 {
	 
	 		if( ! isset($this->settings['channel_id']) OR empty($this->settings['channel_id']))
	 		{
		 		return array();
	 		}
	 		
	 		$sel	= array('channel_fields.field_id',
	 						'channel_fields.field_name',
	 						"CONCAT('field_id_'," . ee()->db->dbprefix . "channel_fields.field_id) AS field",
	 						'channel_fields.field_type',
	 						'channel_fields.field_required',
	 						'channel_fields.field_order',
	 						'channels.channel_id',
	 						'channels.channel_title',
	 						'channels.channel_name',
	 						'channels.field_group');
	 						
	 		$query = ee()->db
	 					->select($sel,FALSE)
	 					->join('channels','channels.field_group=channel_fields.group_id')
	 					->where_in('channels.channel_id',$this->settings['channel_id'])
	 					->where_not_in('channel_fields.field_type',$this->ignore_field_types)
	 					->order_by('channel_fields.field_id')
	 					->get('channel_fields');
	 					

	 		return $query->result();

	 }
	 
	// --------------------------------------------------------------------
	
	
	/**
	 *	Set field arrays.
	 *	@return void
	 */
	public function set_field_arrays()
	{
		foreach($this->field_data as $key=>$row)
		{
			$this->field_ids[$row->field_name] = $row->field;
			$this->field_names[$row->field] = $row->field_name;
		}
		
	}
	
	// --------------------------------------------------------------------
	 
	 /**
	  * Populate array of fields that are multi_selects/checkboxes.
	  * @return void.
	  */
	  public function set_multi_selects()
	  {
	  	
		  foreach($this->field_data as $key=>$row)
		  {
		  	if( in_array($row->field_type,$this->multis))
		  	{
			  	$this->multi_selects[]	= $row->field;
		  	}
		  }
	  }
	  
	// --------------------------------------------------------------------	  
	
		/**
	 * Fetch entry_ids for channel.
	 * @return array
	 */
	 public function entry_ids()
	 {

	 		$data	= array();
	 	
		 	if( ! empty($this->settings['channel_id']))
		 	{
			 $query	= ee()->db
			 			->select('entry_id')
			 			->where_in('channel_id',$this->settings['channel_id'])
			 			->get('channel_titles');
			 			
			 			if($query->num_rows()>0)
			 			{
				 			foreach($query->result() as $key=>$row)
				 			{
					 			$data[]	= $row->entry_id;
				 			}
			 			}
			 			
			 	} 
		 
		 		return $data;
		}
	 
	 // --------------------------------------------------------------------
	 
	 
	 /**
	  * Add a batch of index rows for an entry.
	  *	@param integer
	  *	@return void
	  */
	  public function update_entry_index($entry_id='')
	  {
	  			$str		= '';
		  		$batch	= array();
		  		$entry	= $this->entry($entry_id);
		  		
		  		// Delete current rows with this entry_id.
		  		$delete = ee()->db->where('entry_id',$entry_id)->delete($this->index_table);
		  		
		  		foreach($entry as $key=>$row)
		  		{
			  			$row	= str_replace("\n",'',$row);
			  			$row	= htmlentities($row);
			  			
			  			if(in_array($key,$this->field_ids) && ! empty($row))
			  			{
			  				$str.= strip_tags($row) . '|';
			  			}
			  			
		  		}
		  		
		  		$str = trim($str,'|');
		  		

					// Loop through entry properties to create new batch.
		  		foreach($entry as $key=>$row)
		  		{
			  		
			  		// Create title row.
			  		if($key=='title')
			  		{
			  			$batch[]	=	array('channel_id'=>$entry['channel_id'],'entry_id'=>$entry_id,'field_id'=>'title','entry_text'=>$str,'field_value'=>addslashes($row));
			  		}
			  		
			  		if(in_array($key,$this->multi_selects))
			  		{
				  		
				  		$options 	= explode('|',$row);
				  		
				  		foreach($options as $index=>$option)
				  		{
					  		if( strlen($option) > 0 )
					  		{
						  		$batch[]	= array('channel_id'=>$entry['channel_id'],'entry_id'=>$entry_id,'field_id'=>$key,'entry_text'=>'','field_value'=>$option);
					  		}
				  		}

			  		} elseif( ! in_array($key,$this->ignore_fields)) {
				  		
				  		if($row != '')
				  		{
				  			$batch[]	= array('channel_id'=>$entry['channel_id'],'entry_id'=>$entry_id,'field_id'=>$key,'entry_text'=>'','field_value'=>$row);
				  		}
			  		
			  		}

		  		}
		  		
		  		// Insert the new batch.
		  		$insert	= ee()->db->insert_batch($this->index_table,$batch);
		  		
	  }

	  
	 }
	 // END Class
	
	}

	/* End of file shared.php */
	/* Location: ./system/expressionengine/third_party/gdtindex/libraries/shared.php */
	