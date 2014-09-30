<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
Copyright (C) 2014, Richard Whitmer/Godat Design, Inc.
*/

class Gdtindex {

	
	public	$return_data	= 	'';
	public	$index_table	=	'gdtindex';
	public	$channel_name	=	array();
	public	$channel_id		=	array();
	public	$field_ids		= 	array();
	public	$field_data		=	array();
	public	$field_names	= 	array();
	public	$multi_key		=	'm';
	public	$multis			=	array('multi_select','checkboxes');
	public	$multi_selects	=	array();
	public	$where			=	array();
	public	$where_in		=	array();
	
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		
		

		$this->site_id			= ee()->TMPL->fetch_param('site_id',1);
		$this->status			= ee()->TMPL->fetch_param('status','open');
		
		$this->site_id			= explode('|',$this->site_id);
		$this->status			= explode('|',$this->status);
		
		
		if(ee()->TMPL->fetch_param('channel_name'))
		{
			$this->channel_name		= explode("|",ee()->TMPL->fetch_param('channel_name'));
		}
		
		// Don't do anything more unless there's a channel name.
		
		if( ! empty($this->channel_name))
		{
			
		// Set channel_id
		$this->set_channel_id();
		
		// Set field_data.
		$this->field_data		= $this->channel_field_data();
		
		// Set the field array.
		$this->set_field_arrays();
		
		// Set multi_selects
		$this->set_multi_selects();

		// Look for the field names in the params.
		foreach($this->field_names as $key => $row)
		{
			// Field ids...
			if(ee()->TMPL->fetch_param($key))
			{
				$this->{$key}	= ee()->TMPL->fetch_param($key);
			}
			
			// Field names...
			if(ee()->TMPL->fetch_param($row))
			{
				$this->{$row}	= ee()->TMPL->fetch_param($row);
			}
			
		}
		
		// Set the "where" array from the GET values.
		foreach($this->field_names as $key=>$row)
		{
			if(ee()->input->get($row,TRUE))
			{
				$this->where[$row] = ee()->input->get($row,TRUE);
			}
		}	
		


		}
	}
	
	// --------------------------------------------------------------------
	
	
	
	
	public function rows()
	{
		
		$sel	= array(
						'entry_id',
						'site_id',
						'channel_id',
						'author_id',
						'title',
						'url_title',
						'status',
						'entry_date',
						'year',
						'month',
						'day');
						
		foreach($this->field_names as $field=>$name)
		{
			$sel[]	= $field;
			$sel[]	= $field . ' AS ' . $name;
		}
		
		
		$query = ee()->db
					->select($sel)
					->where_in('status',$this->status)
					->where_in('site_id',$this->site_id)
					->where_in('channel_id',$this->channel_id)
					->order_by('entry_id')
					->get($this->index_table);
		
		return $query->result();		
					
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Which custom fields are multi_select/checkboxes?
	 * @return array
	 */	
	 public function multi_fields()
	 {
	 	$data	= array();
	 	
	 	foreach($this->channel_field_data() as $row)
		 {
			if(in_array($row->field_type,array('multi_select','checkboxes')))
			{
				$data[] = $row->field;
			} 
		 }
		 
		 return $data;
		
	}
	
	// --------------------------------------------------------------------
	
		
	/**
	 * Get data about current channel's field group custom fields.
	 * @return array
	 */
	 public function channel_field_data()
	 {
	 		
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
	 					->where_in('channels.channel_id',$this->channel_id)
	 					->order_by('channel_fields.field_id')
	 					->get('channel_fields');
	 					
	 		return $query->result();
	 		

	 }
	 
	// --------------------------------------------------------------------
	
	
	private function set_field_arrays()
	{
		
		$field_data = $this->channel_field_data();
		
		foreach($field_data as $key=>$row)
		{
			$this->field_ids[$row->field_name] = $row->field;
			$this->field_names[$row->field] = $row->field_name;
		}

		return TRUE;
		
	}
	
	// --------------------------------------------------------------------
	
	/**
	 *	Use the channel_name/s to set the channel_id property.
	 *	@return boolean
	 */
	 private function set_channel_id()
	 {
		 if( ! empty($this->channel_name))
		 {
			 $query = ee()->db
			 			->select('channel_id')
			 			->where_in('site_id',$this->site_id)
			 			->where_in('channel_name',$this->channel_name)
			 			->get('channels');
			 			
			 foreach($query->result() as $row)
			 {
				 $this->channel_id[] = $row->channel_id;
			 }
		 
		 }
		 
		 return TRUE;
	 }
	 
	 
	 // --------------------------------------------------------------------
	 
	 /**
	  * Populate array of fields that are multi_selects/checkboxes.
	  * @return boolean.
	  */
	  private function set_multi_selects()
	  {
	  	
		  foreach($this->field_data as $key=>$row)
		  {
		  	if( in_array($row->field_type,$this->multis))
		  	{
			  	$this->multi_selects[]	= $row->field;
			  	$this->multi_selects[]	= $row->field_name;
		  	}
			  
		  }
	  }
	  
	  
	  // --------------------------------------------------------------------
	  
	  /**
	   * Return a "grouped by" list of items from the index table.
	   */
	   public function grouped_items()
	   {
	   
	   		$data = array();
	   		
	   		if(ee()->TMPL->fetch_param('field_name'))
	   		{
		   		
		   		$field_name = ee()->TMPL->fetch_param('field_name');
		   		
		   		$field_id 	= $this->field_ids[$field_name];
		   		
		   		$query	= ee()->db
		   					->select($field_id)
		   					->where($field_id.' !=','')
		   					->where($field_id.' NOT RLIKE ','\'\\\|\'',FALSE)
		   					->where_in('site_id',$this->site_id)
		   					->where_in('channel_id',$this->channel_id)
		   					->where_in('status',$this->status)
		   					->group_by($field_id)
		   					->order_by($field_id,'ASC')
		   					->get($this->index_table);
		   					
		   		foreach($query->result() as $key=>$row)
		   		{
			   		$data[] = $row->{$field_id};
		   		}
		   		
		   		arsort($data);
		   		$data = array_reverse($data);

	   		}	
	   		
	   		
	   			foreach($data as $key=>$row)
				{
					$data['item'][] = array('item'=>htmlentities($row));	
				}
				

				if(isset($data['item']))
				{
				   	return ee()->TMPL->parse_variables(ee()->TMPL->tagdata,$data['item']);
				}
		   
	   }
	  
	  
	  
	 
	
	
	

}

/* End of file mod.gdtindex.php */
/* Location: ./system/expressionengine/third_party/gdtindex/mod.gdtindex.php */