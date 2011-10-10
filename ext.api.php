<?php

require_once PATH_THIRD.'recognize/config'.EXT;

class Api_ext
{
	
	public $name = 'API, by Mark Huot';
	public $version = '1.0.0';
	public $description = 'Adds an API interface to EE.';
	public $settings_exist = 'n';
	public $docs_url = 'http://docs.markhuot.com/';
	public $settings = array();
	
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	public function sessions_end($session)
	{
		$this->EE->session = $session;
		
		$format = 'json';
		$class = $this->EE->uri->segment(2);
		$method = $this->EE->uri->segment(3);
		
		$segments = array_slice($this->EE->uri->segment_array(), 1);
		$method = array_pop($segments);
		$class = array_shift($segments);
		$params = array_slice($this->EE->uri->uri_to_assoc(3), 0, -1);
		
		if (!$class)
		{
			$class = 'api';
		}
		
		if (preg_match('/^(.*)\.(xml|json)$/', $method, $match) != FALSE)
		{
			$method = $match[1];
			$format = $match[2];
		}
		
		if ($this->_call_api($class, $method, $format, $params))
		{
			die;
		}
	}
	
	private function _call_api($class, $method, $format='json', $params=array())
	{
		if (!$class || !$method)
		{
			return FALSE;
		}
		
		$path = PATH_THIRD."/{$class}/api.{$class}".EXT;
		$class_name = ucfirst("{$class}_api");
		
		if (file_exists($path))
		{
			require_once $path;
		}
		
		$this->EE->load->add_package_path(PATH_THIRD.'/'.$class.'/');
		
		if (class_exists($class_name))
		{
			$api = new $class_name;
			$result = call_user_func_array(array($api, $method), $params);
			
			if ($result === null)
			{
				echo $this->EE->output->final_output;
			}
			
			else
			{
				switch ($format)
				{
					case 'json':
						header('Content-type: application/json');
						echo json_encode($result);
						break;
					
					case 'xml':
						echo "<xml><error>Not Implemented</error></xml>";
						break;
				}
			}
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function activate_extension()
	{
		$this->settings = array(
			/*'max_link_length'	=> 18,
			'truncate_cp_links'	=> 'no',
			'use_in_forum'		=> 'no'*/
		);
		
		foreach (array(
			'sessions_end'
		) as $method)
		{
			if (substr($method, 0, 1) !== '_')
			{
				$methods[] = $method;
			}
		}
		
		foreach ($methods as $hook)
		{
			$this->EE->db->insert('extensions', array(
				'class'		=> __CLASS__,
				'method'	=> $hook,
				'hook'		=> $hook,
				'settings'	=> '',
				'priority'	=> 10,
				'version'	=> $this->version,
				'enabled'	=> 'y'
			));
		}
	}

	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

		if ($current < '2.0')
		{
			// Update to version 2.0
		}

		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('extensions', array('version' => $this->version));
	}

	public function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}
	
}