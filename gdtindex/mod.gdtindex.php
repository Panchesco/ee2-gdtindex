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
	public	$status				=		array('open');

	
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		ee()->lang->loadfile('gdtindex');
		
		// Load the shared library.
		ee()->load->library('shared');

	}
	
	/**
	 *	Handle parameters.
	 *	@return void.
	 */
	 public function param_handler()
	 {
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
			 
			 	$multi_selects	= ee()->shared->multi_selects;

	   		$this->param_handler();
	   		
	   		$where	= array();
	   		
	   		
	   		// If custom field name present in param, limit query with it.
	   		foreach(ee()->shared->field_ids as $fname => $fid)
	   		{   		
		   		if(ee()->TMPL->fetch_param($fname))
		   		{
			   		$where['channel_data.'.$fid]	= ee()->TMPL->fetch_param($fname);
		   		}
	   		}
	   		
	   		$field_name	= ee()->TMPL->fetch_param('field_name');
	   		$field_id		= ee()->shared->field_ids[$field_name];
	   		
		 		$query = ee()->db
		 							->select('channel_data.'.$field_id)
		 							->join('channel_data','channel_data.entry_id=channel_titles.entry_id')
		 							->where('channel_data.'.$field_id.' !=','')
		 							->where($where)
		 							->where_in('channel_titles.status',$this->status)
		 							->where_not_in('channel_data.'.$field_id,$this->ignore)
		 							->group_by('channel_data.'.$field_id)
		 							->get('channel_titles');
	   		
	   		if($query->num_rows()>0)
	   		{						
	   			// Create array we can sort.
	   			$rows	= array();
	   			
	   			foreach($query->result() as $key=>$row)
	   			{
		   			
		   			if( in_array($field_id,$multi_selects))
		   			{
		   					$group = explode('|',$row->{$field_id});
		   					
		   					foreach($group as $value)
		   					{
			   					$rows[]	= $value;
		   					}

		   			} else {
			   			
			   			$rows[]	= $row->{$field_id};
		   			
		   			}

	   			}
	   			
	   			$rows = array_unique($rows);
	   			
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
	   
	   /**
	    * Get the filter_index action id.
	    *	@return integer
	    */
	    public function filter_index_id()
	    {
		    
		    	return ee()->db
		    							->select('action_id')
		    							->limit(1)
		    							->where('class',__CLASS__)
		    							->where('method','filter_index')
		    							->get('actions')
		    							->row()
		    							->action_id;

	    }	 
	    
	    // --------------------------------------------------------------------

	      
	   /**
	    * 
	    *
			*/
			public function filter_index()
			{
			
			

				$data					= array();
				$filtered_ids	= array();
				$sel				= array();
				$where			= array();
				$where_in		= array();
				$option_in	= array();
				
				$entry_ids	= array();

				
				// Build Select.
				
				$sel[]	= 'channel_titles.site_id';
				$sel[]	= 'channel_titles.channel_id';
				$sel[]	= 'channel_titles.entry_id';
				$sel[]	= 'channel_titles.title';
				$sel[]	= 'channel_titles.url_title';
				$sel[]	= 'channel_titles.status';
				$sel[]	= 'channel_titles.entry_date';
				$sel[]	= 'channel_titles.year';
				$sel[]	= 'channel_titles.month';
				$sel[]	= 'channel_titles.day';
				$sel[]	= 'channel_titles.author_id';
				
				
				foreach(ee()->shared->field_ids as $field_name => $id)
				{
					$sel[]	= 'channel_data.'.$id;
					$sel[]	= 'channel_data.'.$id . ' AS ' . $field_name;
				}
				
				
				foreach($_POST as $key=>$row)
				{
					
					$value	= ee()->input->post($key,TRUE);
					$field_id = ee()->shared->field_ids[$key];
					
					if(is_array($value))
					{
					
						$option_in[$field_id]	= $value;
						
					} else {
					
						$where['channel_data.'.$field_id]	= $value;
					}

				}
				
				// Get array of filtered entry_ids
				
				$query	= ee()->db
										->select('channel_titles.entry_id')
										->where($where)
										->where_in('channel_titles.channel_id',ee()->shared->settings['channel_id'])
										->where_in('channel_titles.status',array('open'))
										->join('channel_data','channel_titles.entry_id=channel_data.entry_id')
										->get('channel_titles');
				
				foreach($query->result() as $key => $row)
				{
					$filtered_ids[]	= $row->entry_id;
				}
				
			
			
			// Build out options.
			
			if( empty($option_in))
			{
				
				$data = $filtered_ids;
				
			} else {
			
			
			
				 foreach($option_in as $field_id => $field_value)
				 {
				 
					 	ee()->db->select('entry_id');
						ee()->db->where('field_id',$field_id);
						if( ! empty($filtered_ids))
						{
							ee()->db->where_in('entry_id',$filtered_ids);
						}
						ee()->db->where_in('field_value',$field_value);
						$query = ee()->db->get('gdtindex');
						$result	= $query->result();
						
						$entry_ids 	= array_merge($entry_ids,$result);
				 }
				 
				 
				 
				 	foreach($entry_ids as $key=>$row)
			 		{
				 		$data[]	= $row->entry_id;
			 		}
			 		

			 		
			 		$data = array_unique($data);
			 
			 }
			 
					// EE/CI choking on empty arrays in active record queries. Don't think it's supposed
					// To do that, no time now. Here's a hack.
					if( empty($data))
			 		{
				 		$data = array('0');
			 		}
			 
			 // One more query for the final result set.
			 $query	= ee()->db
										->select($sel)
										->where_in('channel_titles.entry_id',$data)
										->join('channel_data','channel_titles.entry_id=channel_data.entry_id')
										->get('channel_titles');
			
				
				
				$vars['grid'] = $query->result();
				$vars['no_results']	= lang('no_results');
														
				ee()->load->view('product_grid',$vars);
				

				
			}
			
			// --------------------------------------------------------------------


}

/* End of file mod.gdtindex.php */
/* Location: ./system/expressionengine/third_party/gdtindex/mod.gdtindex.php */