<?php
/**
 * @package   	OneAll Social Login
 * @copyright 	Copyright 2013-2016 http://www.oneall.com - All rights reserved.
 * @license   	GNU/GPL 2 or later
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,USA.
 *
 * The "GNU General Public License" (GPL) is available at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 */
define ('VB_API', false);

// Toolbox
require_once (DIR . '/packages/oneallsociallogin/include/toolbox.php');

// Site
class Oneallsociallogin_Api_Site extends vB_Api_Extensions
{
	protected $product = 'oneallsociallogin';
	protected $title = 'OneAll Social Login';
	public $AutoInstall = 1;
	public $version = '1.2.1';
	public $developer = 'OneAll';
	private $settings = null;
	
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////
	// PRIVATE
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////
	
	// Return the settings
	private function get_settings ()
	{
		global $vbulletin;
		
		// Do we need to rebuild the settings?
		if (!is_array ($this->settings))
		{
			// Init settings
			$this->settings = OneAllSocialLogin_Toolbox::get_settings ();
		}
		
		// Done
		return $this->settings;
	}
	
	// Return a specific setting
	private function get_setting ($varname)
	{
		// Read settings
		$settings = $this->get_settings ();
		
		// Return value
		return (isset ($settings [$this->product . '_' . $varname]) ? $settings [$this->product . '_' . $varname] : null);
	}
	
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////
	// PUBLIC
	// ///////////////////////////////////////////////////////////////////////////////////////////////////////
	
	// Display Social Login?
	public function display_social_login ()
	{
		return ((strlen (trim ($this->api_subdomain ())) > 0 && $this->enable_social_login () == 1) ? 1 : 0);
	}	
	
	// Social Login Caption
	public function social_login_caption ()
	{
		return $this->get_setting ('social_login_caption');
	}
	
	// Display Social Link?
	public function display_social_link ()
	{
		return ((strlen (trim ($this->api_subdomain ())) > 0 && $this->enable_social_link () == 1) ? 1 : 0);
	}
	
	// Social Link Caption
	public function social_link_caption ()
	{
		return $this->get_setting ('social_link_caption');
	}
	
	// Return the version
	public function version ()
	{
		return $this->version;
	}
	
	// Return a random id
	public function rnd ()
	{
		return (rand (0, 999999) + 100000);
	}
	
	// Return the API Subdomain
	public function api_subdomain ()
	{
		return $this->get_setting ('api_subdomain');
	}
	
	// Return the API Secret
	public function api_secret ()
	{
		return $this->get_setting ('api_secret');
	}
	
	// Return the API Key
	public function api_key ()
	{
		return $this->get_setting ('api_key');
	}
	
	// Return the API Connector
	public function api_connector ()
	{
		return ($this->get_setting ('api_connector') == 'fsockopen' ? 'fsockopen' : 'curl');
	}
	
	// Return the API Port
	public function api_port ()
	{
		return ($this->get_setting ('api_port') == '80' ? '80' : '443');
	}
	
	// Enable Social Login?
	public function enable_social_login ()
	{
		return ($this->get_setting ('enable_social_login') == 1 ? 1 : 0);
	}

	// Enable Social Link?
	public function enable_social_link ()
	{
		return ($this->get_setting ('enable_social_link') == 1 ? 1 : 0);
	}
	
	// Enable Auto Link?
	public function enable_auto_link ()
	{
		return ($this->get_setting ('enable_auto_link') == 1 ? 1 : 0);
	}
	
	// Enable Avatar Upload?
	public function enable_avatar_upload ()
	{
		return ($this->get_setting ('enable_avatar_upload') == 1 ? 1 : 0);
	}
	
	// Get the list of enabled providers
	public function enabled_providers ()
	{
		// Providers
		$providers = array();
		
		// Read the settings
		$settings = $this->get_settings ();
		
		// Build list
		foreach ($settings as $key => $value)
		{
			if ($value == 1 && preg_match ('/^oneallsociallogin_provider_([a-z0-9\_]+)$/i', $key, $matches))
			{
				$providers [] = $matches [1];
			}
		}
		
		// Done
		return $providers;
	}
	
	// Get the list of enabled providers as JavaScript
	public function enabled_providers_js ()
	{
		return "'" . implode ("', '", $this->enabled_providers ()) . "'";
	}
	
	// Count the number of enabled providers
	public function num_enabled_providers ()
	{
		return count ($this->enabled_providers ());
	}
	
	// User Token of the current user
	public function current_user_user_token ()
	{
		// Read the user info
		$userinfo = vB::getCurrentSession ()->fetch_userinfo ();
		
		// Is the user logged in?
		if (is_array ($userinfo) and !empty ($userinfo ['userid']))
		{
			return OneAllSocialLogin_Toolbox::get_user_token_for_userid ($userinfo ['userid']);
		}
		
		// None found
		return false;
	}
	
	// Session Token of the current user
	public function current_user_session_token ()
	{
		// Read the user info
		$userinfo = vB::getCurrentSession ()->fetch_userinfo ();
	
		// Is the user logged in?
		if (is_array ($userinfo) and !empty ($userinfo ['userid']))
		{
			return OneAllSocialLogin_Toolbox::generate_session_token_for_userid ($userinfo ['userid']);
		}
	
		// None found
		return false;
	}	
}