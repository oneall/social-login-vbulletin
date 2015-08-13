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
 *
 */

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting (E_ALL & ~E_NOTICE);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array(
	'cphome' 
);
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once ('./global.php');
require_once (DIR . '/includes/adminfunctions_options.php');

// ######################## CHECK ADMIN PERMISSIONS ######################
if (!can_administer ('canadminvbsecurity'))
{
	print_cp_no_permission ();
}

// ############################# LOG ACTION ###############################
log_admin_action (iif ($_REQUEST ['action'] != '', 'action = ' . $_REQUEST ['action']));

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// Parse arguments
$vbulletin->input->clean_array_gpc ('r', array(
	'varname' => TYPE_STR,
	'dogroup' => TYPE_STR,
	'do' => TYPE_STR,
	'advanced' => TYPE_BOOL,
	'expand' => TYPE_BOOL,
	'setting' => TYPE_ARRAY 
));

$arguments = $vbulletin->GPC;

// Setup settings phrases
$settingphrase = array();

// Read the OneAll Social Login settings phrases
$phrases = $db->query_read_slave ("
		SELECT varname, text FROM " . TABLE_PREFIX . "phrase 
			WHERE fieldname = 'vbsettings' 
			AND product='oneallsociallogin' 
			AND languageid IN(-1, 0, " . LANGUAGEID . ")
		ORDER BY languageid ASC");
while ( $phrase = $db->fetch_array ($phrases) )
{
	// For a nice look in Settings \ Options, our groups are prefixed with OneAll Social Login:
	if (strpos ($phrase ['varname'], 'settinggroup_') !== false)
	{
		// This prefix is removed if the settings are display in OneAll \ Social Login
		$phrase ['text'] = trim (str_replace ('OneAll Social Login:', '', $phrase ['text']));
	}
	
	// For better remove the titles for the buttons and providers
	if (preg_match ('/^setting_oneallsociallogin_(api_verify|api_autodetect|api_ provider_([^_]+))_title$/i', $phrase ['varname']))
	{
		$phrase ['text'] = '';
	}
	
	// Add text
	$settingphrase [$phrase ['varname']] = $phrase ['text'];
}

// Action
$action = strtolower (trim ($arguments ['do']));

// //////////////////////////////////////////////////////////////////////////////////
// Save Settings
// //////////////////////////////////////////////////////////////////////////////////
if ($action == 'save')
{
	// Do we have the settings?
	if (isset ($arguments ['setting']) && is_array ($arguments ['setting']))
	{
		// Social Networks
		$providers = array();
		
		// Loop through settings
		foreach ($arguments ['setting'] as $setting_key => $setting_value)
		{
			// Make sure it's one of our settings
			if (preg_match ('/^oneallsociallogin_([a-z0-9\_]+)$/i', $setting_key, $key_match))
			{
				// Is it a provider setting?
				if (preg_match ('/^provider_(.+)$/i', $key_match [1], $provider_match))
				{
					// Add to enabled providers
					if (!empty ($setting_value))
					{
						$providers [] = $provider_match [1];
					}
				}
				// Not a provider setting
				else
				{
					// Subdomain
					if ($key_match [1] == 'api_subdomain')
					{
						// Full domain entered
						if (preg_match ("/([a-z0-9\-]+)\.api\.oneall\.com/i", $setting_value, $matches))
						{
							$setting_value = $matches [1];
						}
					}
				}
				
				// Update
				$db->query_write ("UPDATE " . TABLE_PREFIX . "setting SET value = '" . $setting_value . "' WHERE varname='" . $setting_key . "'");
			}
		}
	}
	
	// Show options
	$action = 'display';
}

// //////////////////////////////////////////////////////////////////////////////////
// Display Options
// //////////////////////////////////////////////////////////////////////////////////
if ($action == 'display')
{
	// OneAll Social Login
	print_cp_header ($vbphrase ['oneallsociallogin_title']);
	
	// JavaScript
	echo '<script type="text/javascript" src=" ../clientscript/vbulletin_cpoptions_scripts.js?v=' . SIMPLE_VERSION . '"></script>';
	echo '<script type="text/javascript" src="../oneallsociallogin/include/assets/js/jquery-1.11.3.min.js"></script>';
	echo '<script type="text/javascript">var _j = jQuery.noConflict();</script';
	
	// Settings Cache
	$settingscache = array();	
	
	// Read Settings
	$settings = $db->query_read_slave ("
		SELECT setting.*, settinggroup.grouptitle
		FROM " . TABLE_PREFIX . "settinggroup AS settinggroup
		LEFT JOIN " . TABLE_PREFIX . "setting AS setting USING(grouptitle)
		WHERE settinggroup.product LIKE '%oneall%' AND settinggroup.displayorder <> 0
		ORDER BY settinggroup.displayorder, setting.displayorder
	");	

	while ( $setting = $db->fetch_array ($settings) )
	{
		// Settings
		$settingscache [$setting['grouptitle']] [$setting['varname']] = $setting;

		// Group Titles
		if ( ! isset ($lastgroup) || ($setting ['grouptitle'] != $lastgroup))
		{
			$grouptitlecache [$setting['grouptitle']] = $setting ['grouptitle'];			
		}
		$lastgroup = $setting ['grouptitle'];
	}	
	$db->free_result ($settings);
	
	// Form Header
	print_form_header ('oneallsociallogin', 'save');
	
	// Add Hidden Fields
	construct_hidden_code ('dogroup', $arguments ['dogroup']);
	construct_hidden_code ('advanced', $arguments ['advanced']);
	
	// Settings groups
	$groups = array();
	$groups [] = 'api';
	$groups [] = 'login';
	$groups [] = 'link';
	$groups [] = 'providers';
	
	// Print Settings Tables
	foreach ($groups as $group)
	{
		print_setting_group ('oneallsociallogin_' . $group, $arguments ['advanced']);
	}
	
	// Add submit buttons
	print_submit_row ($vbphrase ['save']);
}

// //////////////////////////////////////////////////////////////////////////////////
// Autodetect API Connector
// //////////////////////////////////////////////////////////////////////////////////
if ($action == 'autodetect')
{
	// Ajax Tools
	require_once (DIR . '/oneallsociallogin/include/ajax.php');
	
	// AutoDetect
	echo OneAllSocialLogin_Ajax::autodetect_api_connection_handler ();
	exit ();
}

// //////////////////////////////////////////////////////////////////////////////////
// Verify API Settings
// //////////////////////////////////////////////////////////////////////////////////
if ($action == 'verify')
{
	// Ajax Tools
	require_once (DIR . '/oneallsociallogin/include/ajax.php');
	
	// Parse request
	$vbulletin->input->clean_array_gpc ('r', array(
		'api_connector' => TYPE_STR,
		'api_key' => TYPE_STR,
		'api_secret' => TYPE_STR,
		'api_port' => TYPE_STR,
		'api_subdomain' => TYPE_STR 
	));
	
	// Setup arguments
	$arguments = $vbulletin->GPC;
	
	// AutoDetect
	echo OneAllSocialLogin_Ajax::verify_api_settings ($arguments);
	exit ();
}