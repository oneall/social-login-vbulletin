<?php
/**
 * @package       OneAll Social Login
 * @copyright     Copyright 2013-2015 http://www.oneall.com - All rights reserved.
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

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('VB_ENTRY', 1);
define('SKIP_REFERRER_CHECK', true);
define('THIS_SCRIPT', 'oa_callback');
define('VB_AREA', 'Forum');

// ######################### REQUIRE BACK-END ############################

chdir(dirname(__FILE__) . "/../../includes");
require_once './init.php';

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// Read arguments
$arguments = vB::getCleaner()->cleanArray($_REQUEST, array(
    'oa_action' => vB_Cleaner::TYPE_STR,
    'connection_token' => vB_Cleaner::TYPE_STR,
    'origin' => vB_Cleaner::TYPE_STR,
    'token' => vB_Cleaner::TYPE_STR
));

// Default Redirect
$redirect_url = vB5_Route::buildUrl('home|fullurl');

// Check if we have a callback and make sure the plugin is enabled
if (!empty($arguments['oa_action']) && !empty($arguments['connection_token']))
{
    // Toolbox
    require_once DIR . '/packages/oneallsociallogin/include/toolbox.php';

    // Read Settings
    $oasl_settings = OneAllSocialLogin_Toolbox::get_settings(true);

    // Is Social Login enabled?
    if (!empty($oasl_settings['enable_social_login']))
    {
        // Read API settings
        $api_key = $oasl_settings['api_key'];
        $api_secret = $oasl_settings['api_secret'];
        $api_subdomain = $oasl_settings['api_subdomain'];

        // Make sure we have the API settings
        if (!empty($api_key) && !empty($api_secret) && !empty($api_subdomain))
        {
            // Communication
            require_once DIR . '/packages/oneallsociallogin/include/communication.php';

            // API Settings.
            $api_connection_handler = ($oasl_settings['api_connector'] == 'fsockopen' ? 'fsockopen' : 'curl');
            $api_connection_use_https = ($oasl_settings['api_port'] == 80 ? false : true);

            // See: http://docs.oneall.com/api/resources/connections/read-connection-details/
            $api_connection_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_subdomain . '.api.oneall.com/connections/' . $arguments['connection_token'] . '.json';

            // API Credentials.
            $api_credentials = array();
            $api_credentials['api_key'] = $api_key;
            $api_credentials['api_secret'] = $api_secret;

            // Make Request.
            $result = OneAllSocialLogin_Communication::do_api_request($api_connection_handler, $api_connection_url, $api_credentials);

            // Extract User Data
            if (($user_data = OneAllSocialLogin_Toolbox::extract_social_network_profile($result)) !== false)
            {
                // This is the user to process
                $userid = null;

                // Social Login
                if ($arguments['oa_action'] == 'social_login')
                {
                    // Get user_id by token.
                    $userid_tmp = OneAllSocialLogin_Toolbox::get_userid_for_user_token($user_data['user_token']);

                    // We already have a user for this token.
                    if (is_numeric($userid_tmp))
                    {
                        // Process this user.
                        $userid = $userid_tmp;
                    }
                    // No user has been found for this token.
                    else
                    {
                        // Make sure that account linking is enabled.
                        if (!empty($oasl_settings['enable_auto_link']))
                        {
                            // Make sure that the email has been verified.
                            if (!empty($user_data['user_email']) && isset($user_data['user_email_is_verified']) && $user_data['user_email_is_verified'] === true)
                            {
                                // Read existing user
                                $userid_tmp = OneAllSocialLogin_Toolbox::get_userid_for_email_address($user_data['user_email']);

                                // Existing user found
                                if (is_numeric($userid_tmp))
                                {
                                    // Link the user to this social network.
                                    if (OneAllSocialLogin_Toolbox::link_tokens_to_user_id($userid_tmp, $user_data['user_token'], $user_data['identity_token'], $user_data['identity_provider']) !== false)
                                    {
                                        $userid = $userid_tmp;
                                    }
                                }
                            }
                        }

                        // No user has been linked to this token yet.
                        if (!is_numeric($userid))
                        {
                            // Username is mandatory.
                            if (!isset($user_data['user_login']) || strlen(trim($user_data['user_login'])) == 0)
                            {
                                $user_data['user_login'] = $user_data['identity_provider'] . 'User';
                            }

                            // Not a random email address.
                            $user_random_email = false;

                            // Email must be unique
                            if (empty($user_data['user_email']) || strlen(trim($user_data['user_email'])) == 0 || OneAllSocialLogin_Toolbox::get_userid_for_email_address($user_data['user_email']) !== false)
                            {
                                // Create a random email address.
                                $user_data['user_email'] = OneAllSocialLogin_Toolbox::generate_random_email_address();

                                // This is a random email address.
                                $user_random_email = true;
                            }

                            // Create a new user
                            $result = OneAllSocialLogin_Toolbox::create_user($user_data);

                            // User Created
                            if (is_array($result) && !empty($result['userid']))
                            {
                                // Generated userid
                                $userid_tmp = $result['userid'];

                                // Link the user to this social network.
                                if (OneAllSocialLogin_Toolbox::link_tokens_to_user_id($userid_tmp, $user_data['user_token'], $user_data['identity_token'], $user_data['identity_provider']) !== false)
                                {
                                    // Process this user.
                                    $userid = $userid_tmp;

                                    // Add the avatar
                                    if (!empty($oasl_settings['enable_avatar_upload']))
                                    {
                                        OneAllSocialLogin_Toolbox::upload_user_avatar($userid, $user_data);
                                    }
                                }
                            }
                        }
                    }

                    // User Found
                    if (isset($userid) && is_numeric($userid))
                    {
                        require_once DIR . '/includes/functions_login.php';
                        require_once DIR . '/includes/functions.php';

                        // Retrieve user information
                        $userinfo = vB_User::fetchUserinfo($userid);

                        // Setup a new session
                        $vbulletin->userinfo = $userinfo;
                        $vbulletin->session->created = true;

                        // Delete existing session
                        vB::getDbAssertor()->delete('session', array(
                            'sessionhash' => vB::getCurrentSession()->get('dbsessionhash')
                        ));

                        // Login the user
                        $result = vB_User::processNewLogin(array(
                            'userid' => $userid
                        ));

                        // Redirect
                        if (!empty($arguments['origin']))
                        {
                            // Security check
                            if (parse_url($arguments['origin'], PHP_URL_HOST) == parse_url($vbulletin->options['frontendurl'], PHP_URL_HOST))
                            {
                                $redirect_url = $arguments['origin'];
                            }
                        }
                    }
                }
                // Social Link
                elseif ($arguments['oa_action'] == 'social_link')
                {
                    // Read userid
                    $userid = OneAllSocialLogin_Toolbox::get_user_for_session_token($arguments['token']);
                    if (!empty($userid))
                    {
                        // Retrieve user information
                        $userinfo = vB_User::fetchUserinfo($userid);
                        if (is_array($userinfo) and !empty($userinfo['userid']))
                        {
                            // Logged in user
                            $userid_curr = $userinfo['userid'];

                            // Update the tokens?
                            $update_tokens = true;

                            // Read the user_id for this user_token
                            $userid_tmp = OneAllSocialLogin_Toolbox::get_userid_for_user_token($user_data['user_token']);

                            // There is already a userid for this token
                            if (!empty($userid_tmp))
                            {
                                // The existing user_id does not match the logged in user
                                if ($userid_tmp != $userid_curr)
                                {
                                    // Do not update the tokens.
                                    $update_tokens = false;
                                }
                            }

                            // Update token?
                            if ($update_tokens === true)
                            {
                                // Link Identity
                                if (!empty($user_data['plugin_action']) && $user_data['plugin_action'] == 'link_identity')
                                {
                                    OneAllSocialLogin_Toolbox::link_tokens_to_user_id($userid_curr, $user_data['user_token'], $user_data['identity_token'], $user_data['identity_provider']);
                                }
                                else
                                {
                                    OneAllSocialLogin_Toolbox::unlink_identity_token($user_data['identity_token']);
                                }
                            }

                            // Redirect
                            if (!empty($arguments['origin']))
                            {
                                // Security check
                                if (parse_url($arguments['origin'], PHP_URL_HOST) == parse_url($vbulletin->options['frontendurl'], PHP_URL_HOST))
                                {
                                    $redirect_url = $arguments['origin'];
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Default
exec_header_redirect($redirect_url);
