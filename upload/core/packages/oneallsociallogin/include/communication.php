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
 */

// API Communication
class OneAllSocialLogin_Communication
{
    // User agent for API requests
    const USER_AGENT = 'SocialLogin/2.3.1 vBulletin/5.x (+http://www.oneall.com/)';

    /**
     * Send an API request by using the given handler
     */
    public static function do_api_request($handler, $url, $options = array(), $timeout = 25)
    {
        // FSOCKOPEN
        if ($handler == 'fsockopen')
        {
            return self::do_fsockopen_request($url, $options, $timeout);
        }
        // CURL
        else
        {
            return self::do_curl_request($url, $options, $timeout);
        }
    }

    /**
     * Check if FSOCKOPEN is available.
     */
    public static function is_fsockopen_available()
    {
        // Make sure fsockopen has been loaded
        if (function_exists('fsockopen') and function_exists('fwrite'))
        {
            // Toolbox
            require_once DIR . '/packages/oneallsociallogin/include/toolbox.php';

            // Read the disabled functions
            $disabled_functions = OneAllSocialLogin_Toolbox::get_disabled_php_functions();

            // Make sure fsockopen has not been disabled
            if (!in_array('fsockopen', $disabled_functions) and !in_array('fwrite', $disabled_functions))
            {
                // Loaded and enabled
                return true;
            }
        }

        // Not loaded or disabled

        return false;
    }

    /**
     * Check if FSOCKOPEN is enabled and can be used to connect to OneAll.
     */
    public static function is_api_connection_fsockopen_ok($secure = true)
    {
        if (self::is_fsockopen_available())
        {
            $result = self::do_fsockopen_request(($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
            if (is_object($result) and property_exists($result, 'http_code') and $result->http_code == 200)
            {
                if (property_exists($result, 'http_data'))
                {
                    if (strtolower($result->http_data) == 'ok')
                    {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Send an FSOCKOPEN request.
     */
    public static function do_fsockopen_request($url, $options = array(), $timeout = 15)
    {
        // Store the result
        $result = new stdClass();

        // Make sure that this is a valid URL
        if (($uri = parse_url($url)) == false)
        {
            $result->http_code = -1;
            $result->http_data = null;
            $result->http_error = 'invalid_uri';

            return $result;
        }

        // Make sure that we can handle the scheme
        switch ($uri['scheme'])
        {
            case 'http':
                $port = (isset($uri['port']) ? $uri['port'] : 80);
                $host = ($uri['host'] . ($port != 80 ? ':' . $port : ''));
                $fp = @fsockopen($uri['host'], $port, $errno, $errstr, $timeout);
                break;

            case 'https':
                $port = (isset($uri['port']) ? $uri['port'] : 443);
                $host = ($uri['host'] . ($port != 443 ? ':' . $port : ''));
                $fp = @fsockopen('ssl://' . $uri['host'], $port, $errno, $errstr, $timeout);
                break;

            default:
                $result->http_code = -1;
                $result->http_data = null;
                $result->http_error = 'invalid_schema';

                return $result;
                break;
        }

        // Make sure that the socket has been opened properly
        if (!$fp)
        {
            $result->http_code = -$errno;
            $result->http_data = null;
            $result->http_error = trim($errstr);

            return $result;
        }

        // Construct the path to act on
        $path = (isset($uri['path']) ? $uri['path'] : '/');
        if (isset($uri['query']))
        {
            $path .= '?' . $uri['query'];
        }

        // Create HTTP request
        $defaults = array(
            'Host' => "Host: $host",
            'User-Agent' => "User-Agent: " . self::USER_AGENT
        );

        // Enable basic authentication
        if (isset($options['api_key']) and isset($options['api_secret']))
        {
            $defaults['Authorization'] = 'Authorization: Basic ' . base64_encode($options['api_key'] . ":" . $options['api_secret']);
        }

        // Build and send request
        $request = 'GET ' . $path . " HTTP/1.0\r\n";
        $request .= implode("\r\n", $defaults);
        $request .= "\r\n\r\n";
        fwrite($fp, $request);

        // Fetch response
        $response = '';
        while (!feof($fp))
        {
            $response .= fread($fp, 1024);
        }

        // Close connection
        fclose($fp);

        // Parse response
        list($response_header, $response_body) = explode("\r\n\r\n", $response, 2);

        // Parse header
        $response_header = preg_split("/\r\n|\n|\r/", $response_header);
        list($header_protocol, $header_code, $header_status_message) = explode(' ', trim(array_shift($response_header)), 3);

        // Build result
        $result->http_code = $header_code;
        $result->http_data = $response_body;

        // Done

        return $result;
    }

    /**
     * Check if CURL has been loaded and is enabled.
     */
    public static function is_curl_available()
    {
        // Make sure cURL has been loaded.
        if (in_array('curl', get_loaded_extensions()) and function_exists('curl_init') and function_exists('curl_exec'))
        {
            // Toolbox
            require_once DIR . '/packages/oneallsociallogin/include/toolbox.php';

            // Read the disabled functions.
            $disabled_functions = OneAllSocialLogin_Toolbox::get_disabled_php_functions();

            // Make sure CURL has not been disabled.
            if (!in_array('curl_init', $disabled_functions) and !in_array('curl_exec', $disabled_functions))
            {
                // Loaded and enabled.
                return true;
            }
        }

        // Not loaded or disabled.

        return false;
    }

    /**
     * Check if CURL is available and can be used to connect to OneAll
     */
    public static function is_api_connection_curl_ok($secure = true)
    {
        // Is CURL available and enabled?
        if (self::is_curl_available())
        {
            // Make a request to the OneAll API.
            $result = self::do_curl_request(($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
            if (is_object($result) and property_exists($result, 'http_code') and $result->http_code == 200)
            {
                if (property_exists($result, 'http_data'))
                {
                    if (strtolower($result->http_data) == 'ok')
                    {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Send a CURL request.
     */
    public static function do_curl_request($url, $options = array(), $timeout = 15, $num_redirects = 0)
    {
        // Store the result
        $result = new \stdClass();

        // Send request
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_REFERER, $url);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, self::USER_AGENT);

        // Does not work in PHP Safe Mode, we manually follow the locations if necessary.
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);

        // BASIC AUTH?
        if (isset($options['api_key']) && isset($options['api_secret']))
        {
            curl_setopt($curl, CURLOPT_USERPWD, $options['api_key'] . ":" . $options['api_secret']);
        }

        // Make request
        if (($response = curl_exec($curl)) !== false)
        {
            // Get Information
            $curl_info = curl_getinfo($curl);

            // Save result
            $result->http_code = $curl_info['http_code'];
            $result->http_headers = preg_split('/\r\n|\n|\r/', trim(substr($response, 0, $curl_info['header_size'])));
            $result->http_data = trim(substr($response, $curl_info['header_size']));
            $result->http_error = null;

            // Check if we have a redirection header
            if (in_array($result->http_code, array(301, 302)) && $num_redirects < 4)
            {
                // Make sure we have http headers
                if (is_array($result->http_headers))
                {
                    // Header found ?
                    $header_found = false;

                    // Loop through headers.
                    while (!$header_found && (list(, $header) = each($result->http_headers)))
                    {
                        // Try to parse a redirection header.
                        if (preg_match("/(Location:|URI:)[^(\n)]*/", $header, $matches))
                        {
                            // Sanitize redirection url.
                            $url_tmp = trim(str_replace($matches[1], "", $matches[0]));
                            $url_parsed = parse_url($url_tmp);
                            if (!empty($url_parsed))
                            {
                                // Header found!
                                $header_found = true;

                                // Follow redirection url.
                                $result = self::do_curl_request($url_tmp, $options, $timeout, $num_redirects + 1);
                            }
                        }
                    }
                }
            }
        }
        else
        {
            $result->http_code = -1;
            $result->http_data = null;
            $result->http_error = curl_error($curl);
        }

        // Done

        return $result;
    }
}
