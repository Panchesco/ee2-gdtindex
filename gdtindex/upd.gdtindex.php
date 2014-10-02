<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 *	Gdtindex_upd
 *
 *	@package		Gdtindex
 *	@author			Richard Whitmer/Godat Design, Inc.
 *	@copyright	(c) 2014, Godat Design, Inc.
 *	@license		
 *	
 *	@link				http://godatdesign.com
 *	@since			Version 1.0
 */
 
 // ------------------------------------------------------------------------


class Gdtindex_upd {

	public $version		= '1.0';
	private $site_id		= array(1);
	private $module_name	= 'Gdtindex';
	private $settings		= array('channel_id'=>array());
	
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// Load Shared library.
		$shared = ee()->load->library('shared');


	}
	
	// --------------------------------------------------------------------
	
		/** 
		 * Get the extension settings.
		 * @return array
		 */
		 function extension_settings()
		 {
		 		return ee()->shared->settings;
			
		 }
	
	// --------------------------------------------------------------------

	function tabs()
	{
		$tabs['download'] = array(
			
				);	
				
		return $tabs;	
	}

	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */	
	function install()
	{
		
		//ee()->load->library('shared');
		
		
		$this->EE->load->dbforge();

		$data = array(
			'module_name' => $this->module_name,
			'module_version' => $this->version,
			'has_cp_backend' => 'n',
			'has_publish_fields' => 'n'
		);

		$this->EE->db->insert('modules', $data);
		
		
		unset($data);
		
		$data = array(
			'class'     => $this->module_name ,
			'method'    => 'filter_index'
	 );

	 ee()->db->insert('actions', $data);

		return TRUE;
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		$this->EE->load->dbforge();

		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => $this->module_name));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', $this->module_name);
		$this->EE->db->delete('modules');
		
		$this->EE->db->where('class', $this->module_name);
		$this->EE->db->delete('actions');
		
		$this->EE->dbforge->drop_table(ee()->shared->index_table);

		/*
		$this->EE->load->library('layout');
		$this->EE->layout->delete_layout_tabs($this->tabs(), 'download');
		*/

		return TRUE;
	}

	// --------------------------------------------------------------------
	
	

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */	
	
	function update($current='')
	{
		return TRUE;
	}
	
	// --------------------------------------------------------------------



	 
	 
	
}
/* END Class */

/* End of file upd.gdtindex.php */
/* Location: ./system/expressionengine/third_party/modules/gdtindex/upd.gdtindex.php */