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
	public static function hook_include_social_login ()
	{
		global $template_hook, $vbulletin;
		
		// Is Social Login enabled?
		if (!empty (OneAllSocialLogin_Toolbox::get_setting ('enable_social_login')))
		{
			$rand = rand (1, 99999) + 100000;
			$providers = "\'".implode ("\', \'", OneAllSocialLogin_Toolbox::get_enabled_providers ())."\'";
			
			$form = <<<HTML

<h2 class="blockhead">Register with a social network</h2>
<div class="blockbody formcontrols">
	<div class="section">
		<div class="blockrow">
			<!-- OneAll.com / Social Login for vBulletin -->
			<div id="oasl_providers_$rand" />
			<script type="text/javascript">
				var _oneall = _oneall || [];
				_oneall.push(
					[\'social_login\', \'set_providers\', [$providers]],
					[\'social_login\', \'set_callback_uri\', \'{vb:raw vboptions.frontendurl}/oneallsociallogin/callback.php?origin=\' + encodeURIComponent (window.location.href)],
					[\'social_login\', \'set_custom_css_uri\', ((("https:" == document.location.protocol) ? "https://secure" : "http://public") + \'.oneallcdn.com/css/api/socialize/themes/vbulletin/default.css\')],
					[\'social_login\', \'do_render_ui\', \'oasl_providers_$rand\']
				);
			</script>
		</div>
	</div>
</div>
</div>
<br />
HTML;
			
			if (isset ($vbulletin->templatecache ['register']))
			{
				$vbulletin->templatecache ['register'] = str_replace ('<form', $form . '<form', $vbulletin->templatecache ['register']);
			}
		}
	}
	
	// Include CSS
	public static function hook_include_frontend_css ()
	{
		// Get the hooks
		global $template_hook, $vboptions;
		
		// Is the product enabled?
		if (OneAllSocialLogin_Toolbox::is_product_enabled ())
		{
			// Build the code
			$code = array();
			$code [] = '<!-- OneAll.com / Social Login for vBulletin -->';
			$code [] = '<link href="' . $vboptions [bburl] . '/oneallsociallogin/include/assets/css/frontend.css" rel="stylesheet" type="text/css" />"';
			
			// Make sure we have such a hook
			if (!isset ($template_hook ['headinclude_css']))
			{
				$template_hook ['headinclude_css'] = '';
			}
			
			// Add library
			$template_hook ['headinclude_css'] .= "\n" . implode ("\n", $code);
		}
	}
	
	// Include JavaScript
	public static function hook_include_frontend_js ()
	{
		// Get the hooks
		global $template_hook;
		
		// Is the product enabled?
		if (OneAllSocialLogin_Toolbox::is_product_enabled ())
		{
			// Read API Subdomain
			$api_subdomain = OneAllSocialLogin_Toolbox::get_setting ('api_subdomain');
			
			// Make sure it's not empty
			if (!empty ($api_subdomain))
			{
				// Build the code
				$code = array();
				$code [] = "<!-- OneAll.com / Social Login for vBulletin -->";
				$code [] = "<script type=\"text/javascript\">";
				$code [] = "<!--";
				$code [] = "  var oal = document.createElement('script'); oal.type = 'text/javascript'; oal.async = true;";
				$code [] = "  oal.src = '//$api_subdomain.api.oneall.com/socialize/library.js';";
				$code [] = "  var oas = document.getElementsByTagName('script')[0]; oas.parentNode.insertBefore(oal, oas);";
				$code [] = "// --";
				$code [] = "</script>";
				
				// Make sure we have such a hook
				if (!isset ($template_hook ['headinclude_javascript']))
				{
					$template_hook ['headinclude_javascript'] = '';
				}
				
				// Add library
				$template_hook ['headinclude_javascript'] .= "\n" . implode ("\n", $code);
			}
		}
	}
	
	// Parse Templates
	public static function parse_templates ()
	{
		// Include the JavaScript
		self::hook_include_frontend_js ();
		
		// Include the CSS
		self::hook_include_frontend_css ();
		
		// Include Social Login
		self::hook_include_social_login ();
	}
}
