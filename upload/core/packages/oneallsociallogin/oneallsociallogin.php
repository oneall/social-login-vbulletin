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

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting (E_ALL & ~E_NOTICE);

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array(
	'cphome' 
);
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
chdir (dirname (__FILE__) . "/../../admincp");
require_once ('global.php');
require_once ('includes/adminfunctions_options.php');

// ############################# LOG ACTION ###############################
log_admin_action ();

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
$arguments = vB::getCleaner ()->cleanArray ($_REQUEST, array(
	'varname' => vB_Cleaner::TYPE_STR,
	'dogroup' => vB_Cleaner::TYPE_STR,
	'do' => vB_Cleaner::TYPE_STR,
	'advanced' => vB_Cleaner::TYPE_BOOL,
	'expand' => vB_Cleaner::TYPE_BOOL,
	'setting' => vB_Cleaner::TYPE_ARRAY 
));

$vb_options = vB::getDatastore ()->getValue ('options');

// Setup settings phrases
$settingphrase = array();

// Read the OneAll Social Login settings phrases
$phrases = vB::getDbAssertor ()->assertQuery ('vBForum:phrase', array(
	vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
	'fieldname' => 'vbsettings',
	'product' => 'oneallsociallogin',
	'languageid' => array(
		-1,
		0,
		LANGUAGEID 
	) 
), array(
	'field' => 'languageid',
	'direction' => vB_dB_Query::SORT_ASC 
));

// Add phrases
if ($phrases and $phrases->valid ())
{
	foreach ($phrases as $phrase)
	{
		$settingphrase [$phrase ['varname']] = $phrase ['text'];
	}
}

// Action
$action = (! empty ($arguments ['do']) ? strtolower ($arguments ['do']) : 'display');

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
				$updateSetting = vB::getDbAssertor ()->assertQuery ('setting', array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
					'value' => $setting_value,
					vB_dB_Query::CONDITIONS_KEY => array(
						array(
							'field' => 'varname',
							'value' => $setting_key,
							'operator' => vB_dB_Query::OPERATOR_EQ 
						) 
					) 
				));
			}
		}
	}
	
	// Show options
	$action = 'display';
}

// //////////////////////////////////////////////////////////////////////////////////
// Autodetect API Connector
// //////////////////////////////////////////////////////////////////////////////////
if ($action == 'autodetect')
{
	// Ajax Tools
	require_once ('include/ajax.php');

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
	require_once ('include/ajax.php');

	// Arguments
	$arguments = vB::getCleaner ()->cleanArray ($_REQUEST, array(
		'api_connector' => vB_Cleaner::TYPE_STR,
		'api_key' => vB_Cleaner::TYPE_STR,
		'api_secret' => vB_Cleaner::TYPE_STR,
		'api_port' => vB_Cleaner::TYPE_STR,
		'api_subdomain' => vB_Cleaner::TYPE_STR
	));

	// AutoDetect
	echo OneAllSocialLogin_Ajax::verify_api_settings ($arguments);
	exit ();
}

// //////////////////////////////////////////////////////////////////////////////////
// Display Options
// //////////////////////////////////////////////////////////////////////////////////
if ($action == 'display')
{
	// OneAll Social Login
	print_cp_header ($vbphrase ['oneallsociallogin_title']);
	
	echo '<script type="text/javascript" src="' . $vb_options ['bburl'] . '/clientscript/vbulletin_cpoptions_scripts.js?v=' . SIMPLE_VERSION . '"></script>';
	
	$settingscache = (isset($settingscache) && is_array ($settingscache) ? $settingscache : array());
	
	$settings = vB::getDbAssertor ()->assertQuery ('vBForum:fetchSettingsByGroup', array(
		vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_METHOD,
		'debug' => true 
	));
	
	foreach ($settings as $setting)
	{
		if ( ! empty ($setting['grouptitle']))
		{
			$grouptitle = $setting['grouptitle'];		
			$varname = $setting['varname'];
			
			$settingscache [$grouptitle] [$varname] = $setting;
			$grouptitlecache [$grouptitle] = $grouptitle;
			$options [$grouptitle] = $settingphrase ['settinggroup_'.$grouptitle];
		}
	}
	
	// Form Action, vBulletin changed this in version 5.2 +
	if (defined ('FILE_VERSION') && (version_compare (FILE_VERSION, '5.2') > 0))
	{
		$form_action = './core/packages/oneallsociallogin/oneallsociallogin';
	}
	else
	{
		$form_action = 'oneallsociallogin';
	}
	
	// Header	
	print_form_header ($form_action, 'save');
	
	// Add Hidden Fields
	construct_hidden_code ('dogroup', $arguments ['dogroup']);
	construct_hidden_code ('advanced', $arguments ['advanced']);
	
	// Print Settings Tables
	foreach (array ('login', 'link', 'api', 'providers') AS $group)
	{
		print_setting_group ('oneallsociallogin_'.$group, $arguments ['advanced']);
	}
	
	// Add submit buttons
	print_submit_row ($vbphrase ['save']);
}