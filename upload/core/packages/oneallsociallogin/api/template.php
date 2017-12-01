<?php
/**
 * @package       OneAll Social Login
 * @copyright     Copyright 2013-2016 http://www.oneall.com - All rights reserved.
 * @license       GNU/GPL 2 or later
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

// Functions
require_once DIR . '/packages/oneallsociallogin/include/toolbox.php';

// Template modification
class Oneallsociallogin_Api_Template extends vB_Api_Extensions
{
    public $product = 'oneallsociallogin';
    public $version = '2.3.1';
    public $developer = 'OneAll';
    public $title = 'Oneall Social Login';
    public $AutoInstall = 1;

    public static function fetchBulk($result, $template_names, $styleid = -1, $type = 'compiled')
    {
        global $vbulletin;

        // Check result
        if (is_array($result) and !empty($result['header']))
        {
            // Make sure the plugin is enabled
            if (OneAllSocialLogin_Toolbox::display_plugin())
            {
                // Template parser
                require_once DIR . '/includes/class_template_parser.php';

                // Setup our login box
                $parser = new vB_TemplateParser('{vb:template display_providers_login_box}');
                $parser->dom_doc = new vB_DomDocument($parser->fetch_dom_compatible());
                $login_box = $parser->_parse_nodes($parser->dom_doc->childNodes());

                // From 5.3.4 result['header'] include a new tpl which contain login modal named top_menu_user
                // for non logged user
                if (empty($vbulletin->userinfo['userid']))
                {
                    // $find_string = 'idLoginIframeContainer';
                    $top_menu_user_tpl = vB_Template::create('top_menu_user');
                    $top_menu_user_string = $top_menu_user_tpl->render();

                    $result['header'] = preg_replace('/\' \. vB_Template_Runtime::includeTemplate\(\'top_menu_user\'(.*)\. \'/i', $top_menu_user_string, $result['header']);
                }

                // Replace
                $result['header'] = preg_replace('/<li\s+id=(["\']{1})idLoginIframeContainer/i', $login_box . '\0', $result['header']);
            }
        }

        // Check result
        if (is_array($result) and !empty($result['widget_register']))
        {
            // Make sure the plugin is enabled
            if (OneAllSocialLogin_Toolbox::display_plugin())
            {
                // Template parser
                require_once DIR . '/includes/class_template_parser.php';

                // Setup our login box
                $parser = new vB_TemplateParser('{vb:template display_providers_login_box_register}');
                $parser->dom_doc = new vB_DomDocument($parser->fetch_dom_compatible());
                $login_box = $parser->_parse_nodes($parser->dom_doc->childNodes());

                // From 5.3.4 result['header'] include a new tpl which contain login modal named top_menu_user
                // for non logged user
                if (empty($vbulletin->userinfo['userid']))
                {
                    // Replace
                    $result['widget_register'] = preg_replace('/<div class="b-module\' \. vB_Template_Runtime::vBVar\(\$widgetConfig\[\'show_at_breakpoints_css_classes\'\]\) \. \' canvas-widget registration-widget/i', $login_box . '\0', $result['widget_register']);
                }
            };
        }

        return $result;
    }
}
