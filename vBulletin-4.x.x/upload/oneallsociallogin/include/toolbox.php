<?php
/**
 * @package   	OneAll Social Login
 * @copyright 	Copyright 2013-2015 http://www.oneall.com - All rights reserved.
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

// Toolbox
class OneAllSocialLogin_Toolbox
{
	// Cache
	public static $settings_cache = null;

	/**
	 * Return all settings.
	 */
	public static function get_settings ()
	{
		global $vbulletin;
		
		// Do we have the settings in the cache ?
		if (!is_array (self::$settings_cache))
		{
			// Build the cache
			self::$settings_cache = array();
			
			// Loop through results
			$settings = $vbulletin->db->query_read ("SELECT varname, value FROM " . TABLE_PREFIX . "setting WHERE grouptitle LIKE 'oneallsociallogin%'");
			while ( $setting = $vbulletin->db->fetch_array ($settings) )
			{
				// Without Prefix
				if (preg_match ('/^oneallsociallogin_([a-z0-9\_]+)$/i', $setting ['varname'], $matches))
				{
					self::$settings_cache [$matches [1]] = $setting ['value'];
				}
			}
		}
		
		// Done
		return self::$settings_cache;
	}

	/**
	 * Return a specific setting.
	 */
	public static function get_setting ($varname)
	{
		// Read settings
		$settings = self::get_settings ();
		
		// Return value
		return (isset ($settings [$varname]) ? $settings [$varname] : null);
	}

	/**
	 * Return the list of disabled PHP functions.
	 */
	public static function get_disabled_php_functions ()
	{
		$disabled_functions = trim (ini_get ('disable_functions'));
		if (strlen ($disabled_functions) == 0)
		{
			$disabled_functions = array();
		}
		else
		{
			$disabled_functions = explode (',', $disabled_functions);
			$disabled_functions = array_map ('trim', $disabled_functions);
		}
		return $disabled_functions;
	}

	/**
	 * Return the list of enabled providers
	 */
	public static function get_enabled_providers ()
	{
		// Providers
		$providers = array();
		
		// Read the settings
		$settings = self::get_settings ();
		
		// Build list
		foreach ($settings as $key => $value)
		{
			if ($value == 1 && preg_match ('/^provider_([a-z0-9\_]+)$/i', $key, $matches))
			{
				$providers [] = $matches [1];
			}
		}
		
		// Done
		return $providers;
	}

	/**
	 * Return the protocol of the request.
	 */
	public static function get_request_protocol ()
	{
		if (!empty ($_SERVER ['SERVER_PORT']))
		{
			if (trim ($_SERVER ['SERVER_PORT']) == '443')
			{
				return 'https';
			}
		}
		
		if (!empty ($_SERVER ['HTTP_X_FORWARDED_PROTO']))
		{
			if (strtolower (trim ($_SERVER ['HTTP_X_FORWARDED_PROTO'])) == 'https')
			{
				return 'https';
			}
		}
		
		if (!empty ($_SERVER ['HTTPS']))
		{
			if (strtolower (trim ($_SERVER ['HTTPS'])) == 'on' or trim ($_SERVER ['HTTPS']) == '1')
			{
				return 'https';
			}
		}
		
		return 'http';
	}

	/**
	 * Return the url of the request.
	 */
	function get_request_url ($remove_vars = array ('oa_social_login_login_token', 'sid'))
	{
		global $request;
		
		// Extract Uri
		if (strlen (trim ($_SERVER ['REQUEST_URI'])) > 0)
		{
			$request_uri = trim ($_SERVER ['REQUEST_URI']);
		}
		else
		{
			$request_uri = trim ($_SERVER ['PHP_SELF']);
		}
		$request_uri = htmlspecialchars_decode ($request_uri);
		
		// Extract Protocol
		$request_protocol = self::get_request_protocol ();
		
		// Extract Host
		if (strlen (trim ($_SERVER ['HTTP_X_FORWARDED_HOST'])) > 0)
		{
			$request_host = trim ($_SERVER ['HTTP_X_FORWARDED_HOST']);
		}
		elseif (strlen (trim ($_SERVER ['HTTP_HOST'])) > 0)
		{
			$request_host = trim ($_SERVER ['HTTP_HOST']);
		}
		else
		{
			$request_host = trim ($_SERVER ['SERVER_NAME']);
		}
		
		// Port of this request
		$request_port = '';
		
		// We are using a proxy
		if (strlen (trim ($_SERVER ['HTTP_X_FORWARDED_PORT'])) > 0)
		{
			// SERVER_PORT is usually wrong on proxies, don't use it!
			$request_port = intval ($_SERVER ['HTTP_X_FORWARDED_PORT']);
		}
		// Does not seem like a proxy
		else if (strlen (trim ($_SERVER ['SERVER_PORT'])) > 0)
		{
			$request_port = intval ($_SERVER ['SERVER_PORT']);
		}
		
		// Remove standard ports
		$request_port = (!in_array ($request_port, array(
			80,
			443 
		)) ? $request_port : '');
		
		// Build url
		$current_url = $request_protocol . '://' . $request_host . (!empty ($request_port) ? (':' . $request_port) : '') . $request_uri;
		
		// Remove query arguments.
		if (is_array ($remove_vars) && count ($remove_vars) > 0)
		{
			// Break up url
			list ($url_part, $query_part) = array_pad (explode ('?', $current_url), 2, '');
			parse_str ($query_part, $query_vars);
			
			// Remove argument.
			if (is_array ($query_vars))
			{
				foreach ($remove_vars as $var)
				{
					if (isset ($query_vars [$var]))
					{
						unset ($query_vars [$var]);
					}
				}
				
				// Build new url
				$current_url = $url_part . ((is_array ($query_vars) and count ($query_vars) > 0) ? ('?' . http_build_query ($query_vars)) : '');
			}
		}
		
		// Done
		return $current_url;
	}
	
	// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// USER
	// ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	/**
	 * Create a new user
	 */
	public static function create_user ($user_data)
	{
		global $vbulletin;
		
		// Group
		$usergroupid = ($vbulletin->options ['moderatenewmembers'] ? 4 : 2);
		
		// Password
		$userpassword = self::generate_hash (10);
		
		// Setup user
		$user = &datamanager_init ('User', $vbulletin, ERRTYPE_ARRAY);
		$user->set ('email', $user_data ['user_email']);
		$user->set ('username', $user_data ['user_login_clean']);
		$user->set ('password', $userpassword);
		$user->set ('usergroupid', $usergroupid);
		$user->set ('languageid', $vbulletin->userinfo ['languageid']);
		$user->set_usertitle ('', false, $vbulletin->usergroupcache [$usergroupid], false, false);
		$user->set ('ipaddress', IPADDRESS);
		$user->pre_save ();
		
		// Errors
		if (empty ($user->errors))
		{
			// Save
			$userid = $user->save ();
			
			// Success
			if ($userid)
			{
				// Set Rang
				$userinfo = fetch_userinfo ($userid, 0, 0, 0, true);
				$userdata_rank = &datamanager_init ('User', $vbulletin, ERRTYPE_SILENT);
				$userdata_rank->set_existing ($userinfo);
				$userdata_rank->set ('posts', 0);
				$userdata_rank->save ();
				
				// Done
				return array(
					'userid' => $userid,
					'password' => $userpassword 
				);
			}
		}
		
		// Error
		return false;
	}

	/**
	 * Upload a new avatar
	 */
	public static function upload_user_avatar ($userid, $social_data)
	{
		global $vbulletin, $permissions;
		
		// Check format
		if (is_array ($social_data) && (!empty ($social_data ['user_thumbnail']) || !empty ($social_data ['user_picture'])))
		{
			// User Info
			$userinfo = fetch_userinfo ($userid, 0, 0, 0, true);
			
			// Init User datamanager
			$userdata = &datamanager_init ('User', $vbulletin, ERRTYPE_STANDARD);
			$userdata->set_existing ($userinfo);
			
			// Can we upload avatars ?
			if ($vbulletin->bf_ugp_genericpermissions ['canprofilepic'])
			{
				// Toolbox
				require_once (DIR . '/oneallsociallogin/include/communication.php');
				
				// Use this avatar
				$user_avatar_url = (!empty ($social_data ['user_picture']) ? $social_data ['user_picture'] : $social_data ['user_thumbnail']);
				
				// Which connection handler do we have to use?
				$api_connection_handler = (self::get_setting ('api_connector') == 'fsockopen' ? 'fsockopen' : 'curl');
				
				// Retrieve file data
				$api_result = OneAllSocialLogin_Communication::do_api_request ($api_connection_handler, $user_avatar_url);
				
				// Success?
				if (is_object ($api_result) && property_exists ($api_result, 'http_code') && $api_result->http_code == 200)
				{
					// Width
					$min_width = 1;
					$max_width = 9999;
					
					// Height
					$min_height = 1;
					$max_height = 9999;
					
					// Get avatar max sizes
					if (!empty ($userinfo ['usergroupid']))
					{
						$sql = "SELECT usergroupid, avatarmaxwidth, avatarmaxheight FROM " . TABLE_PREFIX . "usergroup WHERE usergroupid  = '" . intval ($userinfo ['usergroupid']) . "'";
						$result = $vbulletin->db->query_first ($sql);
						if (is_array ($result) && !empty ($result ['usergroupid']))
						{
							if ($result ['avatarmaxwidth'] > 0)
							{
								$max_width = $result ['avatarmaxwidth'];
							}
							
							if ($result ['avatarmaxheight'] > 0)
							{
								$max_height = $result ['avatarmaxheight'];
							}
						}
					}
					
					// File data
					$file_data = $api_result->http_data;
					
					// Temporary filename
					$file_tmp_name = ($vbulletin->options ['tmppath'] . '/vbupload-' . $userinfo ['userid'] . '-' . time () . '-' . rand (1000, 9999) . '.tmp');
					
					// Save file
					if (($fp = @fopen ($file_tmp_name, 'wb')) !== false)
					{
						// Write file
						$avatar_size = fwrite ($fp, $file_data);
						fclose ($fp);
						
						// Allowed file extensions
						$file_exts = array();
						$file_exts [IMAGETYPE_GIF] = 'gif';
						$file_exts [IMAGETYPE_JPEG] = 'jpg';
						$file_exts [IMAGETYPE_PNG] = 'png';
						
						// Get image data
						list ($width, $height, $type, $attr) = @getimagesize ($file_tmp_name);
						
						// Check image size and type
						if ($width > $min_width && $height > $min_height && isset ($file_exts [$type]))
						{
							// File extension
							$file_ext = $file_exts [$type];
							
							// Check if we can resize the image if needd
							if (function_exists ('imagecreatetruecolor') && function_exists ('imagecopyresampled'))
							{
								// Check if we need to resize
								if ($width > $max_width || $height > $max_height)
								{
									// Keep original size
									$orig_height = $height;
									$orig_width = $width;
									
									// Taller
									if ($height > $max_height)
									{
										$width = ($max_height / $height) * $width;
										$height = $max_height;
									}
									
									// Wider
									if ($width > $max_width)
									{
										$height = ($max_width / $width) * $height;
										$width = $max_width;
									}
									
									// Destination
									$destination = imagecreatetruecolor ($width, $height);
									
									// Resize
									switch ($file_ext)
									{
										case 'gif' :
											$source = imagecreatefromgif ($file_tmp_name);
											imagecopyresampled ($destination, $source, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
											imagegif ($destination, $file_tmp_name);
										break;
										
										case 'png' :
											$source = imagecreatefrompng ($file_tmp_name);
											imagecopyresampled ($destination, $source, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
											imagepng ($destination, $file_tmp_name);
										break;
										
										case 'jpg' :
											$source = imagecreatefromjpeg ($file_tmp_name);
											imagecopyresampled ($destination, $source, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
											imagejpeg ($destination, $file_tmp_name);
										break;
									}
								}
							}
							
							// Final path
							$avatar_name = "avatar" . $userid . "_" . ($userinfo ['avatarrevision'] + 1) . "." . $file_exts [$type];
							$avatar_full_name = rtrim ($vbulletin->options ['avatarpath'], ' /') . '/' . $avatar_name;
							
							// Move file
							if (@copy ($file_tmp_name, $avatar_full_name))
							{
								// Save
								$userpic = &datamanager_init ('Userpic_Avatar', $vbulletin, ERRTYPE_STANDARD, 'userpic');
								$userpic->set ('dateline', TIMENOW);
								$userpic->set ('userid', $userid);
								$userpic->set ('filename', $avatar_name);
								$userpic->set ('filedata', file_get_contents ($avatar_full_name));
								$userpic->set ('width', $width);
								$userpic->set ('height', $height);
								$userpic->save ();
								
								// Save
								$userdata->set ('avatarrevision', $userinfo ['avatarrevision'] + 1);
								$userdata->save ();
								
								// Remove temporary file
								@unlink ($file_tmp_name);
								
								// Done
								return true;
							}
						}
						
						// Error
						@unlink ($file_tmp_name);
						return false;
					}
				}
			}
		}
		
		// Error
		return false;
	}

	/**
	 * Unlinks the identity token
	 */
	public static function unlink_identity_token ($identity_token)
	{
		global $vbulletin;
		
		// Delete the identity_token.
		$sql = "DELETE FROM " . TABLE_PREFIX . "oasl_identity WHERE identity_token = '" . $vbulletin->db->escape_string ($identity_token) . "'";
		$vbulletin->db->query ($sql);
	}

	/**
	 * Links the user/identity tokens to a user
	 */
	public static function link_tokens_to_user_id ($userid, $user_token, $identity_token, $identity_provider)
	{
		global $vbulletin;
		
		// Make sure that that the user exists.
		$sql = "SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid  = '" . intval ($userid) . "'";
		$result = $vbulletin->db->query_first ($sql);
		if (is_array ($result) && !empty ($result ['userid']))
		{
			$userid = $result ['userid'];
			$oasl_userid = null;
			$oasl_identityid = null;
			
			// Delete superfluous user_token.
			$sql = "SELECT oasl_userid FROM " . TABLE_PREFIX . "oasl_user WHERE userid = '" . intval ($userid) . "' AND user_token <> '" . $vbulletin->db->escape_string ($user_token) . "'";
			$results = $vbulletin->db->query_read ($sql);
			while ( $result = $vbulletin->db->fetch_array ($results) )
			{
				// Delete the wrongly linked user_token.
				$sql = "DELETE FROM " . TABLE_PREFIX . "oasl_user WHERE oasl_userid = '" . intval ($result ['oasl_userid']) . "'";
				$vbulletin->db->query ($sql);
				
				// Delete the wrongly linked identity_token.
				$sql = "DELETE FROM " . TABLE_PREFIX . "oasl_identity WHERE oasl_userid = '" . intval ($result ['oasl_userid']) . "'";
				$vbulletin->db->query ($sql);
			}
			
			// Read the entry for the given user_token.
			$sql = "SELECT oasl_userid FROM " . TABLE_PREFIX . "oasl_user WHERE user_token = '" . $vbulletin->db->escape_string ($user_token) . "'";
			$result = $vbulletin->db->query_first ($sql);
			if (is_array ($result) && !empty ($result ['oasl_userid']))
			{
				$oasl_userid = $result ['oasl_userid'];
			}
			
			// The user_token either does not exist or has been reset.
			if (empty ($oasl_userid))
			{
				// Add new link.
				$sql = "INSERT INTO " . TABLE_PREFIX . "oasl_user SET userid='" . intval ($userid) . "', user_token='" . $vbulletin->db->escape_string ($user_token) . "', date_created='" . time () . "'";
				$vbulletin->db->query_write ($sql);
				
				// Identifier of the newly created user_token entry.
				$oasl_userid = $vbulletin->db->insert_id ();
			}
			
			// Read the entry for the given identity_token.
			$sql = "SELECT oasl_identityid, oasl_userid FROM " . TABLE_PREFIX . "oasl_identity WHERE identity_token = '" . $vbulletin->db->escape_string ($identity_token) . "'";
			$result = $vbulletin->db->query_first ($sql);
			if (is_array ($result) && !empty ($result ['oasl_identityid']))
			{
				$oasl_identityid = $result ['oasl_identityid'];
				
				// The identity_token is linked to another user_token.
				if (!empty ($result ['oasl_userid']) && $result ['oasl_userid'] != $oasl_userid)
				{
					// Delete the wrongly linked identity_token.
					$sql = "DELETE FROM " . TABLE_PREFIX . "oasl_identity WHERE oasl_identityid = '" . intval ($oasl_identityid) . "'";
					$vbulletin->db->query ($sql);
					
					// Reset the identifier
					$oasl_identityid = null;
				}
			}
			
			// The identity_token either does not exist or has been reset.
			if (empty ($oasl_identityid))
			{
				// Add new link.
				$sql = "INSERT INTO " . TABLE_PREFIX . "oasl_identity SET oasl_userid='" . intval ($oasl_userid) . "', identity_token='" . $vbulletin->db->escape_string ($identity_token) . "', identity_provider='" . $vbulletin->db->escape_string ($identity_provider) . "', num_logins=1, date_added='" . time () . "', date_updated='" . time () . "'";
				$vbulletin->db->query_write ($sql);
				
				// Identifier of the newly created identity_token entry.
				$oasl_identity_id = $vbulletin->db->insert_id ();
			}
			
			// Done.
			return true;
		}
		
		// An error occured.
		return false;
	}

	/**
	 * Get the userid for the given session token.
	 */
	public static function get_user_for_session_token ($session_token, $read_once = true)
	{
		global $vbulletin;
		
		// Read the userid for this username
		$sql = "SELECT oasl_sessionid, userid FROM " . TABLE_PREFIX . "oasl_session WHERE session_token  = '" . $vbulletin->db->escape_string (trim ($session_token)) . "'";
		$result = $vbulletin->db->query_first ($sql);
		if (is_array ($result) && !empty ($result ['userid']))
		{
			// Remove the entry
			if ($read_once)
			{
				$sql = "DELETE FROM " . TABLE_PREFIX . "oasl_session WHERE oasl_sessionid = '" . intval ($result ['oasl_sessionid']) . "'";
				$vbulletin->db->query ($sql);
			}
			
			// Done
			return $result ['userid'];
		}
		
		// Not found.
		return false;
	}

	/**
	 * Generates a session token for the given user_id.
	 */
	public static function generate_session_token_for_userid ($userid)
	{
		global $vbulletin;
		
		// Make sure it's not empty
		if (!empty ($userid))
		{
			// Cleanup sessions.
			$sql = "DELETE FROM " . TABLE_PREFIX . "oasl_session WHERE userid = '" . intval ($userid) . "' OR date_created < '" . (time () - 3600) . "'";
			$vbulletin->db->query ($sql);
			
			// Generate a token
			$session_token = self::generate_hash (36);
			
			// Add new session.
			$sql = "INSERT INTO " . TABLE_PREFIX . "oasl_session SET userid='" . intval ($userid) . "', session_token='" . $vbulletin->db->escape_string ($session_token) . "', date_created='" . time () . "'";
			$vbulletin->db->query_write ($sql);
			
			// Done
			return $session_token;
		}
		
		// Invalid user
		return false;
	}

	/**
	 * Get the userid for a given a username.
	 */
	public static function get_userid_for_username ($username)
	{
		global $vbulletin;
		
		// Read the userid for this username
		$sql = "SELECT userid FROM " . TABLE_PREFIX . "user WHERE username  = '" . $vbulletin->db->escape_string (trim ($username)) . "'";
		$result = $vbulletin->db->query_first ($sql);
		if (is_array ($result) && !empty ($result ['userid']))
		{
			return $result ['userid'];
		}
		
		// Not found.
		return false;
	}

	/**
	 * Returns the userid for a given email address.
	 */
	public static function get_userid_for_email_address ($email_address)
	{
		global $vbulletin;
		
		// Make sure it is not empty.
		$email_address = trim ($email_address);
		if (strlen ($email_address) == 0)
		{
			return false;
		}
		
		// Read the userid for this email_address.
		$sql = "SELECT userid FROM " . TABLE_PREFIX . "user WHERE email = '" . $vbulletin->db->escape_string ($email_address) . "'";
		$result = $vbulletin->db->query_first ($sql);
		if (is_array ($result) && !empty ($result ['userid']))
		{
			return $result ['userid'];
		}
		
		// Not found.
		return false;
	}

	/**
	 * Returns the user_token for a given userid
	 */
	public static function get_user_token_for_userid ($userid)
	{
		global $vbulletin;
		
		// Read the userid for this user_token.
		$sql = "SELECT user_token FROM " . TABLE_PREFIX . "oasl_user WHERE userid = '" . intval ($userid) . "'";
		$result = $vbulletin->db->query_first ($sql);
		if (is_array ($result) && !empty ($result ['user_token']))
		{
			return $result ['user_token'];
		}
		
		// Not found
		return false;
	}

	/**
	 * Returns the user_id for a given token.
	 */
	public static function get_userid_for_user_token ($user_token)
	{
		global $vbulletin;
		
		// Make sure it is not empty.
		$user_token = trim ($user_token);
		if (strlen ($user_token) == 0)
		{
			return false;
		}
		
		// Read the userid for this user_token.
		$sql = "SELECT oasl_userid, userid FROM " . TABLE_PREFIX . "oasl_user WHERE user_token = '" . $vbulletin->db->escape_string ($user_token) . "'";
		$result = $vbulletin->db->query_first ($sql);
		if (is_array ($result) && !empty ($result ['userid']))
		{
			$userid = $result ['userid'];
			$oasl_userid = $result ['oasl_userid'];
			
			// Check if the user account exists.
			$sql = "SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid = '" . intval ($userid) . "'";
			$result = $vbulletin->db->query_first ($sql);
			if (is_array ($result) && !empty ($result ['userid']))
			{
				// The user account exists, return it's identifier.
				return $result ['userid'];
			}
			
			// Delete the wrongly linked user_token.
			$sql = "DELETE FROM " . TABLE_PREFIX . "oasl_user WHERE oasl_userid = '" . intval ($oasl_userid) . "'";
			$vbulletin->db->query ($sql);
			
			// Delete the wrongly linked identity_token.
			$sql = "DELETE FROM " . TABLE_PREFIX . "oasl_identity WHERE oasl_userid = '" . intval ($oasl_userid) . "'";
			$vbulletin->db->query ($sql);
		}
		
		// No entry found.
		return false;
	}

	/**
	 * Inverts CamelCase -> camel_case.
	 */
	public static function undo_camel_case ($input)
	{
		$result = $input;
		
		if (preg_match_all ('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches))
		{
			$ret = $matches [0];
			
			foreach ($ret as &$match)
			{
				$match = ($match == strtoupper ($match) ? strtolower ($match) : lcfirst ($match));
			}
			
			$result = implode ('_', $ret);
		}
		
		return $result;
	}

	/**
	 * Generates a random hash of the given length
	 */
	protected function generate_hash ($length)
	{
		$hash = '';
		
		for($i = 0; $i < $length; $i ++)
		{
			do
			{
				$char = chr (mt_rand (48, 122));
			}
			while ( !preg_match ('/[a-zA-Z0-9]/', $char) );
			$hash .= $char;
		}
		
		// Done
		return $hash;
	}

	/**
	 * Generates a random email address
	 */
	protected function generate_random_email_address ()
	{
		do
		{
			$email_address = self::generate_hash (10) . "@example.com";
		}
		while ( self::get_userid_for_email_address ($email_address) !== false );
		
		// Done
		return $email_address;
	}

	/**
	 * Extracts the social network data from a result-set returned by the OneAll API.
	 */
	public static function extract_social_network_profile ($social_data)
	{
		// Check API result.
		if (is_object ($social_data) && property_exists ($social_data, 'http_code') && $social_data->http_code == 200 && property_exists ($social_data, 'http_data'))
		{
			// Decode the social network profile Data.
			$social_data = @json_decode ($social_data->http_data);
			
			// Make sur that the data has beeen decoded properly
			if (is_object ($social_data))
			{
				// Container for user data
				$data = array();
				
				// Parse plugin data.
				if (isset ($social_data->response->result->data->plugin))
				{
					// Plugin.
					$plugin = $social_data->response->result->data->plugin;
					
					// Add plugin data.
					$data ['plugin_key'] = $plugin->key;
					$data ['plugin_action'] = (isset ($plugin->data->action) ? $plugin->data->action : null);
					$data ['plugin_operation'] = (isset ($plugin->data->operation) ? $plugin->data->operation : null);
					$data ['plugin_reason'] = (isset ($plugin->data->reason) ? $plugin->data->reason : null);
					$data ['plugin_status'] = (isset ($plugin->data->status) ? $plugin->data->status : null);
				}
				
				// Do we have a user?
				if (isset ($social_data->response->result->data->user) && is_object ($social_data->response->result->data->user))
				{
					// User.
					$user = $social_data->response->result->data->user;
					
					// Add user data.
					$data ['user_token'] = $user->user_token;
					
					// Do we have an identity ?
					if (isset ($user->identity) && is_object ($user->identity))
					{
						// Identity.
						$identity = $user->identity;
						
						// Add identity data.
						$data ['identity_token'] = $identity->identity_token;
						$data ['identity_provider'] = $identity->source->name;
						
						$data ['user_first_name'] = !empty ($identity->name->givenName) ? $identity->name->givenName : '';
						$data ['user_last_name'] = !empty ($identity->name->familyName) ? $identity->name->familyName : '';
						$data ['user_formatted_name'] = !empty ($identity->name->formatted) ? $identity->name->formatted : '';
						$data ['user_location'] = !empty ($identity->currentLocation) ? $identity->currentLocation : '';
						$data ['user_constructed_name'] = trim ($data ['user_first_name'] . ' ' . $data ['user_last_name']);
						$data ['user_picture'] = !empty ($identity->pictureUrl) ? $identity->pictureUrl : '';
						$data ['user_thumbnail'] = !empty ($identity->thumbnailUrl) ? $identity->thumbnailUrl : '';
						$data ['user_current_location'] = !empty ($identity->currentLocation) ? $identity->currentLocation : '';
						$data ['user_about_me'] = !empty ($identity->aboutMe) ? $identity->aboutMe : '';
						$data ['user_note'] = !empty ($identity->note) ? $identity->note : '';
						
						// Birthdate - MM/DD/YYYY
						if (!empty ($identity->birthday) && preg_match ('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', $identity->birthday, $matches))
						{
							$data ['user_birthdate'] = str_pad ($matches [2], 2, '0', STR_PAD_LEFT);
							$data ['user_birthdate'] .= '/' . str_pad ($matches [1], 2, '0', STR_PAD_LEFT);
							$data ['user_birthdate'] .= '/' . str_pad ($matches [3], 4, '0', STR_PAD_LEFT);
						}
						else
						{
							$data ['user_birthdate'] = '';
						}
						
						// Fullname.
						if (!empty ($identity->name->formatted))
						{
							$data ['user_full_name'] = $identity->name->formatted;
						}
						elseif (!empty ($identity->name->displayName))
						{
							$data ['user_full_name'] = $identity->name->displayName;
						}
						else
						{
							$data ['user_full_name'] = $data ['user_constructed_name'];
						}
						
						// Preferred Username.
						if (!empty ($identity->preferredUsername))
						{
							$data ['user_login'] = $identity->preferredUsername;
						}
						elseif (!empty ($identity->displayName))
						{
							$data ['user_login'] = $identity->displayName;
						}
						else
						{
							$data ['user_login'] = $data ['user_full_name'];
						}
						
						// Login without spaces
						$data ['user_login_clean'] = str_replace (' ', '', trim ($data ['user_login']));
						
						// Clean
						$data ['user_login_clean'] = htmlspecialchars_decode ($data ['user_login_clean']);
						
						// Website/Homepage.
						$data ['user_website'] = '';
						if (!empty ($identity->profileUrl))
						{
							$data ['user_website'] = $identity->profileUrl;
						}
						elseif (!empty ($identity->urls [0]->value))
						{
							$data ['user_website'] = $identity->urls [0]->value;
						}
						
						// Gender.
						$data ['user_gender'] = '';
						if (!empty ($identity->gender))
						{
							switch ($identity->gender)
							{
								case 'male' :
									$data ['user_gender'] = 'm';
								break;
								
								case 'female' :
									$data ['user_gender'] = 'f';
								break;
							}
						}
						
						// Email Addresses.
						$data ['user_emails'] = array();
						$data ['user_emails_simple'] = array();
						
						// Email Address.
						$data ['user_email'] = '';
						$data ['user_email_is_verified'] = false;
						
						// Extract emails.
						if (property_exists ($identity, 'emails') && is_array ($identity->emails))
						{
							// Loop through emails.
							foreach ($identity->emails as $email)
							{
								// Add to simple list.
								$data ['user_emails_simple'] [] = $email->value;
								
								// Add to list.
								$data ['user_emails'] [] = array(
									'user_email' => $email->value,
									'user_email_is_verified' => $email->is_verified 
								);
								
								// Keep one, if possible a verified one.
								if (empty ($data ['user_email']) || $email->is_verified)
								{
									$data ['user_email'] = $email->value;
									$data ['user_email_is_verified'] = $email->is_verified;
								}
							}
						}
						
						// Addresses.
						$data ['user_addresses'] = array();
						$data ['user_addresses_simple'] = array();
						
						// Extract entries.
						if (property_exists ($identity, 'addresses') && is_array ($identity->addresses))
						{
							// Loop through entries.
							foreach ($identity->addresses as $address)
							{
								// Add to simple list.
								$data ['user_addresses_simple'] [] = $address->formatted;
								
								// Add to list.
								$data ['user_addresses'] [] = array(
									'formatted' => $address->formatted 
								);
							}
						}
						
						// Phone Number.
						$data ['user_phone_numbers'] = array();
						$data ['user_phone_numbers_simple'] = array();
						
						// Extract entries.
						if (property_exists ($identity, 'phoneNumbers') && is_array ($identity->phoneNumbers))
						{
							// Loop through entries.
							foreach ($identity->phoneNumbers as $phone_number)
							{
								// Add to simple list.
								$data ['user_phone_numbers_simple'] [] = $phone_number->value;
								
								// Add to list.
								$data ['user_phone_numbers'] [] = array(
									'value' => $phone_number->value,
									'type' => (isset ($phone_number->type) ? $phone_number->type : null) 
								);
							}
						}
						
						// URLs.
						$data ['user_interests'] = array();
						$data ['user_interests_simple'] = array();
						
						// Extract entries.
						if (property_exists ($identity, 'interests') && is_array ($identity->interests))
						{
							// Loop through entries.
							foreach ($identity->interests as $interest)
							{
								// Add to simple list.
								$data ['user_interests_simple'] [] = $interest->value;
								
								// Add to list.
								$data ['users_interests'] [] = array(
									'value' => $interest->value,
									'category' => (isset ($interest->category) ? $interest->category : null) 
								);
							}
						}
						
						// URLs.
						$data ['user_urls'] = array();
						$data ['user_urls_simple'] = array();
						
						// Extract entries.
						if (property_exists ($identity, 'urls') && is_array ($identity->urls))
						{
							// Loop through entries.
							foreach ($identity->urls as $url)
							{
								// Add to simple list.
								$data ['user_urls_simple'] [] = $url->value;
								
								// Add to list.
								$data ['user_urls'] [] = array(
									'value' => $url->value,
									'type' => (isset ($url->type) ? $url->type : null) 
								);
							}
						}
						
						// Certifications.
						$data ['user_certifications'] = array();
						$data ['user_certifications_simple'] = array();
						
						// Extract entries.
						if (property_exists ($identity, 'certifications') && is_array ($identity->certifications))
						{
							// Loop through entries.
							foreach ($identity->certifications as $certification)
							{
								// Add to simple list.
								$data ['user_certifications_simple'] [] = $certification->name;
								
								// Add to list.
								$data ['user_certifications'] [] = array(
									'name' => $certification->name,
									'number' => (isset ($certification->number) ? $certification->number : null),
									'authority' => (isset ($certification->authority) ? $certification->authority : null),
									'start_date' => (isset ($certification->startDate) ? $certification->startDate : null) 
								);
							}
						}
						
						// Recommendations.
						$data ['user_recommendations'] = array();
						$data ['user_recommendations_simple'] = array();
						
						// Extract entries.
						if (property_exists ($identity, 'recommendations') && is_array ($identity->recommendations))
						{
							// Loop through entries.
							foreach ($identity->recommendations as $recommendation)
							{
								// Add to simple list.
								$data ['user_recommendations_simple'] [] = $recommendation->value;
								
								// Build data.
								$data_entry = array(
									'value' => $recommendation->value 
								);
								
								// Add recommender
								if (property_exists ($recommendation, 'recommender') && is_object ($recommendation->recommender))
								{
									$data_entry ['recommender'] = array();
									
									// Add recommender details
									foreach (get_object_vars ($recommendation->recommender) as $field => $value)
									{
										$data_entry ['recommender'] [self::undo_camel_case ($field)] = $value;
									}
								}
								
								// Add to list.
								$data ['user_recommendations'] [] = $data_entry;
							}
						}
						
						// Accounts.
						$data ['user_accounts'] = array();
						
						// Extract entries.
						if (property_exists ($identity, 'accounts') && is_array ($identity->accounts))
						{
							// Loop through entries.
							foreach ($identity->accounts as $account)
							{
								// Add to list.
								$data ['user_accounts'] [] = array(
									'domain' => (isset ($account->domain) ? $account->domain : null),
									'userid' => (isset ($account->userid) ? $account->userid : null),
									'username' => (isset ($account->username) ? $account->username : null) 
								);
							}
						}
						
						// Photos.
						$data ['user_photos'] = array();
						$data ['user_photos_simple'] = array();
						
						// Extract entries.
						if (property_exists ($identity, 'photos') && is_array ($identity->photos))
						{
							// Loop through entries.
							foreach ($identity->photos as $photo)
							{
								// Add to simple list.
								$data ['user_photos_simple'] [] = $photo->value;
								
								// Add to list.
								$data ['user_photos'] [] = array(
									'value' => $photo->value,
									'size' => $photo->size 
								);
							}
						}
						
						// Languages.
						$data ['user_languages'] = array();
						$data ['user_languages_simple'] = array();
						
						// Extract entries.
						if (property_exists ($identity, 'languages') && is_array ($identity->languages))
						{
							// Loop through entries.
							foreach ($identity->languages as $language)
							{
								// Add to simple list
								$data ['user_languages_simple'] [] = $language->value;
								
								// Add to list.
								$data ['user_languages'] [] = array(
									'value' => $language->value,
									'type' => $language->type 
								);
							}
						}
						
						// Educations.
						$data ['user_educations'] = array();
						$data ['user_educations_simple'] = array();
						
						// Extract entries.
						if (property_exists ($identity, 'educations') && is_array ($identity->educations))
						{
							// Loop through entries.
							foreach ($identity->educations as $education)
							{
								// Add to simple list.
								$data ['user_educations_simple'] [] = $education->value;
								
								// Add to list.
								$data ['user_educations'] [] = array(
									'value' => $education->value,
									'type' => $education->type 
								);
							}
						}
						
						// Organizations.
						$data ['user_organizations'] = array();
						$data ['user_organizations_simple'] = array();
						
						// Extract entries.
						if (property_exists ($identity, 'organizations') && is_array ($identity->organizations))
						{
							// Loop through entries.
							foreach ($identity->organizations as $organization)
							{
								// At least the name is required.
								if (!empty ($organization->name))
								{
									// Add to simple list.
									$data ['user_organizations_simple'] [] = $organization->name;
									
									// Build entry.
									$data_entry = array();
									
									// Add all fields.
									foreach (get_object_vars ($organization) as $field => $value)
									{
										$data_entry [self::undo_camel_case ($field)] = $value;
									}
									
									// Add to list.
									$data ['user_organizations'] [] = $data_entry;
								}
							}
						}
					}
				}
				return $data;
			}
		}
		return false;
	}
}