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
require_once (DIR . '/oneallsociallogin/include/toolbox.php');

// Integration
class OneAllSocialLogin_Integration
{
	// Include Social Login
	public static function hook_include_social_link ()
	{
		global $template_hook, $vbulletin, $vboptions;
		
		// The user must be logged in for Social Link
		if (!empty ($vbulletin->userinfo ['userid']))
		{
			// User Data
			$oasl_user_token = OneAllSocialLogin_Toolbox::get_user_token_for_userid ($vbulletin->userinfo ['userid']);
			$oasl_session_token = OneAllSocialLogin_Toolbox::generate_session_token_for_userid ($vbulletin->userinfo ['userid']);
			$oasl_providers = OneAllSocialLogin_Toolbox::get_enabled_providers ();
			
			// Profile Settings
			if (isset ($vbulletin->templatecache ['modifyprofile']))
			{
				// Build Template
				$templater = vB_Template::create ('oneallsociallogin_social_link_profile');
				$templater->register ('oasl_rand', (rand (1, 99999) + 100000));
				$templater->register ('oasl_caption', OneAllSocialLogin_Toolbox::get_setting ('social_link_caption'));
				$templater->register ('oasl_custom_css', '');
				$templater->register ('oasl_callback_url', ($vbulletin->options ['bburl'] . '/oneallsociallogin/callback.php'));
				$templater->register ('oasl_providers', implode ("', '", $oasl_providers));
				$templater->register ('oasl_user_token', $oasl_user_token);
				$templater->register ('oasl_session_token', $oasl_session_token);
				
				// Retrieve contents (single-quotes break eval, we need to escape them)
				$contents = $templater->render ();
				$contents = str_replace ("'", "\'", $contents);
				
				// Inject it to the template
				$vbulletin->templatecache ['modifyprofile'] = str_replace ('<form', $contents . '<form', $vbulletin->templatecache ['modifyprofile']);
			}
			
			// Generic
			$templater = vB_Template::create ('oneallsociallogin_social_link');
			$templater->register ('oasl_rand', (rand (1, 99999) + 100000));
			$templater->register ('oasl_caption', OneAllSocialLogin_Toolbox::get_setting ('social_link_caption'));
			$templater->register ('oasl_custom_css', '');
			$templater->register ('oasl_callback_url', ($vbulletin->options ['bburl'] . '/oneallsociallogin/callback.php'));
			$templater->register ('oasl_providers', implode ("', '", $oasl_providers));
			$templater->register ('oasl_user_token', $oasl_user_token);
			$templater->register ('oasl_session_token', $oasl_session_token);
			$contents = $templater->render ();
			
			// {vb:raw vboptions.oneallsociallogin}
			$vbulletin->options ['oneallsociallogin_link'] = $contents;
		}
	}
	
	// Include Social Login
	public static function hook_include_social_login ()
	{
		global $template_hook, $vbulletin, $vboptions;
		
		// Registration page
		if (isset ($vbulletin->templatecache ['register']))
		{
			// Build Template
			$templater = vB_Template::create ('oneallsociallogin_social_login_registration');
			$templater->register ('oasl_rand', (rand (1, 99999) + 100000));
			$templater->register ('oasl_caption', OneAllSocialLogin_Toolbox::get_setting ('social_login_reg_caption'));
			$templater->register ('oasl_custom_css', '');
			$templater->register ('oasl_callback_url', ($vbulletin->options ['bburl'] . '/oneallsociallogin/callback.php'));
			$templater->register ('oasl_providers', implode ("', '", OneAllSocialLogin_Toolbox::get_enabled_providers ()));
			
			// Retrieve contents (single-quotes break eval, we need to escape them)
			$contents = $templater->render ();
			$contents = str_replace ("'", "\'", $contents);
			
			// Inject it to the template
			$vbulletin->templatecache ['register'] = str_replace ('<form', $contents . '<form', $vbulletin->templatecache ['register']);
		}
		
		// Header
		if (isset ($vbulletin->templatecache ['header']))
		{
			// Build Template
			$templater = vB_Template::create ('oneallsociallogin_social_login_top');
			$templater->register ('oasl_rand', (rand (1, 99999) + 100000));
			$templater->register ('oasl_caption', OneAllSocialLogin_Toolbox::get_setting ('social_login_top_caption'));
			$templater->register ('oasl_custom_css', '');
			$templater->register ('oasl_callback_url', ($vbulletin->options ['bburl'] . '/oneallsociallogin/callback.php'));
			$templater->register ('oasl_providers', implode ("', '", OneAllSocialLogin_Toolbox::get_enabled_providers ()));
			
			// Retrieve contents (single-quotes break eval, we need to escape them)
			$contents = $templater->render ();
			$contents = str_replace ("'", "\'", $contents);
			
			// Replace
			if (($tmp = preg_replace ('/<\s*ul.+nouser[^>]+>/i', '\\0' . $contents, $vbulletin->templatecache ['header'])) !== null)
			{
				$vbulletin->templatecache ['header'] = $tmp;
			}
		}
		
		// Generic
		$templater = vB_Template::create ('oneallsociallogin_social_login');
		$templater->register ('oasl_rand', (rand (1, 99999) + 100000));
		$templater->register ('oasl_custom_css', '');
		$templater->register ('oasl_callback_url', ($vbulletin->options ['bburl'] . '/oneallsociallogin/callback.php'));
		$templater->register ('oasl_providers', implode ("', '", OneAllSocialLogin_Toolbox::get_enabled_providers ()));
		$contents = $templater->render ();
		
		// {vb:raw vboptions.oneallsociallogin}
		$vbulletin->options ['oneallsociallogin_login'] = $contents;
	}
	
	// Include CSS
	public static function hook_include_frontend_css ()
	{
		// Get the hooks
		global $template_hook, $vboptions;
		
		// Build the code
		$code = array();
		$code [] = '<!-- OneAll.com / Social Login for vBulletin -->';
		$code [] = '<link href="' . $vboptions [bburl] . '/oneallsociallogin/include/assets/css/frontend.css" rel="stylesheet" type="text/css" />';
		
		// Make sure we have such a hook
		if (!isset ($template_hook ['headinclude_css']))
		{
			$template_hook ['headinclude_css'] = '';
		}
		
		// Add library
		$template_hook ['headinclude_css'] .= "\n" . implode ("\n", $code);
	}
	
	// Include JavaScript
	public static function hook_include_frontend_js ()
	{
		// Get the hooks
		global $template_hook;
		
		// Read API Subdomain
		$api_subdomain = OneAllSocialLogin_Toolbox::get_setting ('api_subdomain');
		
		// Make sure it's not empty
		if (!empty ($api_subdomain))
		{
			// Build Template
			$templater = vB_Template::create ('oneallsociallogin_library');
			$templater->register ('oasl_subdomain', $api_subdomain);
			
			// Retrieve contents (single-quotes break eval, we need to escape them)
			$contents = $templater->render ();
			$contents = str_replace ("'", "\'", $contents);
			
			// Make sure we have such a hook
			if (!isset ($template_hook ['headinclude_javascript']))
			{
				$template_hook ['headinclude_javascript'] = '';
			}
			
			// Add library
			$template_hook ['headinclude_javascript'] .= $contents;
		}
	}
	
	// Parse Templates
	public static function parse_templates ()
	{
		// Which services are enabled?
		$use_social_login = OneAllSocialLogin_Toolbox::get_setting ('enable_social_login');
		$use_social_link = OneAllSocialLogin_Toolbox::get_setting ('enable_social_link');
		
		// At least one should be enabled
		if (!empty ($use_social_login) or !empty ($use_social_link))
		{
			// Include the JavaScript
			self::hook_include_frontend_js ();
			
			// Include the CSS
			self::hook_include_frontend_css ();
			
			// Is Social Login enabled?
			if (!empty ($use_social_login))
			{
				self::hook_include_social_login ();
			}
			
			// Is Social Link enabled?
			if (!empty ($use_social_link))
			{
				self::hook_include_social_link ();
			}
		}
	}
}