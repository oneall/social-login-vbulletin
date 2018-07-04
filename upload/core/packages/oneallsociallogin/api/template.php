<?php
/**
 * @package       OneAll Social Login
 * @copyright     Copyright 2013-2018 http://www.oneall.com - All rights reserved.
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
    public $version = '2.3.3';
    public $developer = 'OneAll';
    public $title = 'Oneall Social Login';
    public $AutoInstall = 1;

    public static function processReplacementVars($html, $styleid = -1)
    {
        // Make sure the plugin is enabled
        if (OneAllSocialLogin_Toolbox::display_plugin())
        {
            require_once DIR . '/includes/class_template_parser.php';

            // Register page
            if (preg_match('/<li\s+id=(["\']{1})idLoginIframeContainer/i', $html))
            {
                //Load OA tpl
                $top_menu_user_tpl = vB_Template::create('display_providers_login_box');

                // load vBulletin default variables
                $vboptions = vB5_Template_Options::instance()->getOptions();
                $vboptions = $vboptions['options'];
                $top_menu_user_tpl->register('vboptions', $vboptions, true);

                // get and set OA vars to tpl
                $oa = new Oneallsociallogin_Api_Site('site');
                $top_menu_user_tpl->register('oasl_providers', $oa->enabled_providers_js());
                $top_menu_user_tpl->register('oasl_version', $oa->version());
                $top_menu_user_tpl->register('oasl_num_providers', $oa->num_enabled_providers());
                $top_menu_user_tpl->register('oasl_social_login_caption', $oa->social_login_caption());

                //get tpl html
                $top_menu_user_string = $top_menu_user_tpl->render();

                // Replace
                $html = preg_replace('/<li\s+id=(["\']{1})idLoginIframeContainer/i', $top_menu_user_string . '\0', $html);
            }

            // Register page
            if (preg_match('/<div\s+class=(["\']{1}).*registration-widget/i', $html))
            {
                //Load OA tpl
                $top_menu_user_tpl = vB_Template::create('display_providers_login_box_register');

                // load vBulletin default variables
                $vboptions = vB5_Template_Options::instance()->getOptions();
                $vboptions = $vboptions['options'];
                $top_menu_user_tpl->register('vboptions', $vboptions, true);

                // get and set OA vars to tpl
                $oa = new Oneallsociallogin_Api_Site('site');
                $top_menu_user_tpl->register('oasl_providers', $oa->enabled_providers_js());
                $top_menu_user_tpl->register('oasl_version', $oa->version());
                $top_menu_user_tpl->register('oasl_num_providers', $oa->num_enabled_providers());
                $top_menu_user_tpl->register('oasl_social_login_caption', $oa->social_login_caption());

                //get tpl html
                $top_menu_user_string = $top_menu_user_tpl->render();

                // Replace
                $html = preg_replace('/<div\s+class=(["\']{1}).*registration-widget/i', $top_menu_user_string . '\0', $html);
            }
        }

        return $html;
    }
}
