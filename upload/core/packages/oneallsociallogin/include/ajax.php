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
 */

// Communication
require_once (DIR . '/packages/oneallsociallogin/include/communication.php');

// Ajax
class OneAllSocialLogin_Ajax
{
	// Autodetect API Handler
	public static function autodetect_api_connection_handler ()
	{
		// Check if CURL is available
		if (OneAllSocialLogin_Communication::is_curl_available ())
		{
			// Check CURL HTTPS - Port 443
			if (OneAllSocialLogin_Communication::is_api_connection_curl_ok (true) === true)
			{
				return 'success|curl_443|Detected CURL on port 443';
			}
			// Check CURL HTTP - Port 80
			elseif (OneAllSocialLogin_Communication::is_api_connection_curl_ok (false) === true)
			{
				return 'success|curl_80|Detected CURL on port 80';
			}
			else
			{
				return 'error|curl_ports_blocked|CURL seems to be working, but both outgoing ports are blocked';
			}
		}
		// Check if FSOCKOPEN is available
		elseif (OneAllSocialLogin_Communication::is_fsockopen_available ())
		{
			// Check FSOCKOPEN HTTPS - Port 443
			if (OneAllSocialLogin_Communication::is_api_connection_fsockopen_ok (true) == true)
			{
				return 'success|fsockopen_443|Detected FSOCKOPEN on port 443';
			}
			// Check FSOCKOPEN HTTP - Port 80
			elseif (OneAllSocialLogin_Communication::is_api_connection_fsockopen_ok (false) == true)
			{
				return 'success|fsockopen_80|Detected FSOCKOPEN on port 80';
			}
			else
			{
				return 'error|fsockopen_ports_blocked|FSOCKOPEN seems to be working, but both outgoing ports are blocked';
			}
		}
		
		// No working handler found
		return 'error|no_handler|Could neither detect CURL nor FSOCKOPEN';
	}

	/**
	 * Verify the API settings
	 */
	public static function verify_api_settings ($arguments)
	{
		// API Settings
		$api_subdomain = (!empty ($arguments ['api_subdomain']) ? trim (strtolower ($arguments ['api_subdomain'])) : '');
		$api_key = (!empty ($arguments ['api_key']) ? trim (strtolower ($arguments ['api_key'])) : '');
		$api_secret = (!empty ($arguments ['api_secret']) ? trim (strtolower ($arguments ['api_secret'])) : '');
		$api_connector = ((!empty ($arguments ['api_connector']) and $arguments ['api_connector'] == 'fsockopen') ? 'fsockopen' : 'curl');
		$api_port = ((!empty ($arguments ['api_port']) and $arguments ['api_port'] == 80) ? 80 : 443);
		
		// All fields need to filled out
		if (empty ($api_subdomain) || empty ($api_key) || empty ($api_secret))
		{
			return ('error|fields_missing|Please fill out all of the API fields');
		}
		
		// Full domain entered
		if (preg_match ("/([a-z0-9\-]+)\.api\.oneall\.com/i", $api_subdomain, $matches))
		{
			$api_subdomain = $matches [1];
		}
		
		// Check subdomain format
		if (!preg_match ("/^[a-z0-9\-]+$/i", $api_subdomain))
		{
			return 'error|subdomain_wrong_syntax|The API subdomain does not exist';
		}
		
		// Domain
		$api_domain = $api_subdomain . '.api.oneall.com';
		
		// Connection to the API
		$api_resource_url = ($api_port == 80 ? 'http' : 'https') . '://' . $api_domain . '/tools/ping.json';
	
		// Get connection details
		$result = OneAllSocialLogin_Communication::do_api_request ($api_connector, $api_resource_url, array('api_key' => $api_key, 'api_secret' => $api_secret), 15);
		
		// Parse result
		if (is_object ($result) and property_exists ($result, 'http_code') and property_exists ($result, 'http_data'))
		{
			switch ($result->http_code)
			{
				// Success
				case 200 :
					return 'success|settings_correct|The API settings are correct';
				break;
				
				// Authentication Error
				case 401 :
					return 'error|authentication_credentials_wrong|The API Credentials are not correct';
				break;
				
				// Wrong Subdomain
				case 404 :
					return 'error|authentication_subdomain_wrong|The API Subdomain does not exist';
				break;
			}
		}
		return 'error|communication|Could not establish a communication with the OneAll API. Please check your API settings.';
	}
}
