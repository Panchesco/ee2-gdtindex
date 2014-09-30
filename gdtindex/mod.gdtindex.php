<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *	Gdtindex
 *
 *	@package		Gdtindex
 *	@author			Richard Whitmer/Godat Design, Inc.
 *	@copyright	(c) 2014, Godat Design, Inc.
 *	@link				http://godatdesign.com
 *	@since			Version 1.0
 */
 
 // ------------------------------------------------------------------------

class Gdtindex {

	
	public	$return_data	= 	'';
	public	$channel_ids	=		array();
	public	$ignore				=		array('');

	
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();


		ee()->lang->loadfile('gdtindex');
		
		// Load the shared library.
		ee()->load->library('shared');
		

		if(ee()->TMPL->fetch_param('channel_name'))
		{
			$this->channel_name		= explode("|",ee()->TMPL->fetch_param('channel_name'));
		}
		
		// Don't do anything more unless there's a channel name.
		
		if( ! empty($this->channel_name))
		{
			
				foreach($this->channel_name as $key=>$row)
				{
					$this->channel_ids[]	= ee()->shared->channel_ids[$row];
				}
				
				
				$this->order_by		= ee()->TMPL->fetch_param('order_by','field_value');
	   		$this->sort				= ee()->TMPL->fetch_param('sort','ASC');
	   		$this->sort				= strtoupper($this->sort);
	   		if(ee()->TMPL->fetch_param('ignore'))
	   		{
		   		$this->ignore = explode('|',ee()->TMPL->fetch_param('ignore'));
	   		};				

		} else {
			
			$this->return_data = lang('no_channel');
			
		}
		
		
	}
	
	
	// --------------------------------------------------------------------

	  /**
	   *	Return a "grouped by" list of items from the index table.
	   *	@return mixed array/NULL
	   */
	   public function grouped_items()
	   {
	   
	   		$data = array();
	   		
	   		$field_name	= ee()->TMPL->fetch_param('field_name',FALSE);
	   		
	   		// Verify field name exists.
	   		if(in_array($field_name,ee()->shared->field_names))
	   		{
		   		$field_id	= ee()->shared->field_ids[$field_name];
		   		
	   		} else {
		   		
		   		return NULL;
	   		}
		 		

	   		$query	= ee()->db
	   								->select('field_id,field_value')
	   								->where_in('channel_id',$this->channel_ids)
	   								->where('field_id',$field_id)
	   								->where_not_in('field_value',$this->ignore)
	   								->group_by('field_value')
	   								->order_by($this->order_by,$this->sort)
	   								->get(ee()->shared->index_table);
	   		
	   		
	   		if($query->num_rows()>0)
	   		{						
	   			// Create array we can sort.
	   			$rows	= array();
	   			
	   			foreach($query->result() as $key=>$row)
	   			{
		   			$rows[]	= $row->field_value;
	   			}
	   			
	   			natsort($rows);
	   			
	   			if($this->sort=='DESC')
	   			{
	   				$rows = array_reverse($rows);
	   			}
	   			
	   			foreach($rows as $key=>$row)
	   			{
		   			$data[]	= array('item'=>$row);
			 		}
			 		
			 		return ee()->TMPL->parse_variables(ee()->TMPL->tagdata,$data);
			 		
			 		} else {
	   		
			 			return NULL;
			 		
	   		}

	   }
	  
	  
	  
	 
	
	
	

}

/* End of file mod.gdtindex.php */
/* Location: ./system/expressionengine/third_party/gdtindex/mod.gdtindex.php */