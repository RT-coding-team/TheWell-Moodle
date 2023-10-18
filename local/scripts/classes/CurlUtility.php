<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A utility for making cURL requests. This is a modified version of the file in
 * ChatAttachments plugin.
 */
class CurlUtility
{
    /**
     * The status code of the request
     *
     * @var integer
     * @access public
     */
    public $responseCode = 0;

    /**
     * The last URL visited by cURL
     *
     * @var string
     * @access public
     **/
    public $lastVisitedURL = '';

    /**
     * The main url to request
     *
     * @var integer
     * @access public
     */
    private $url = '';

    /**
     * The authorization bearer token (if required)
     *
     * @var string
     */
    private $token = '';

    /**
     * Set up the class
     *
     * @param string    $url    The URL
     * @param string    $token  The token to access the API
     *
     * @throws InvalidArgumentException     If the URL is not set

     */
    public function __construct($url, $token = '') {
        if ($url === '') {
            throw new InvalidArgumentException('You must provide a valid URL.');
        }
        if (substr($url, -1) !== '/') {
            /**
             * Add the trailing slash if missing
             */
            $this->url = $url . '/';
        } else {
            $this->url = $url;
        }
        $this->token = $token;
    }

    /**
     * Make a cURL Request
     *
     * @param string        $path       the path to request
     * @param string        $method     the method to use POST or GET
     * @param array|string  $data       The data to send (string or array)
     * @param string        $filepath   A path to a file to send.
     * @param string        $isJson     Is the data JSON? (Send data as a string)
     * @return string
     * @access public
     * @throws  InvalidArgumentException    If you supply a filepath, but send as a GET
     * @throws  InvalidArgumentException    If you supply a filepath, but send data as a string
     * @throws  InvalidArgumentException    If you supply a filepath, but the file does not exist
     */
    public function makeRequest($path, $method, $data, $filepath = null, $isJson = false)
    {
        $url = $this->url . '' . ltrim($path, '/');
        $method = strtoupper($method);
        $headers = [];

        if ($isJson) {
            $headers[] = 'Content-Type: application/json';
        }
        if (($filepath) && ($method !== 'POST')) {
            throw new InvalidArgumentException('If you supply a filepath, the method must be POST.');
        }

        if (($filepath) && (!is_array($data))) {
            throw new InvalidArgumentException('If you supply a filepath, the data must be an array.');
        }

        if (($filepath) && (!file_exists($filepath))) {
            throw new InvalidArgumentException('If you supply a filepath, the file must exist.');
        }

        if ($filepath) {
            $mimeType = mime_content_type($filepath);
            $data['file'] = new CURLFile($filepath, $mimeType, basename($filepath));
        }

        /**
         * open connection
         */
        $ch = curl_init();
        $payload = $data;
        if ($method == 'GET') {
            if (is_array($data)) {
                $payload = $this->urlify($data);
            }
            $url = $url . '?' . $payload;
        }
        /**
         * Setup cURL, we start by spoofing the user agent since it is from code:
         * http://davidwalsh.name/set-user-agent-php-curl-spoof
         */
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13'
        );

		// Add the authorization headers
        if (!empty($this->token)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        /**
         * Follow all redirections
         **/
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
        /**
         * execute request
         */
        $result = curl_exec($ch);// or die(curl_error($ch));
        $this->responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->lastVisitedURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        /**
         * close connection
         */
        curl_close($ch);
        return $result;
    }

    /**
     * Download a file locally
     *
     * @param  string $sourcePath  The path of the file on the url
     * @param  string $destination The destination of the file
     * @return boolean             Successfully downloaded?
     *
     * @link https://stackoverflow.com/a/6409531/4638563
     */
    public function downloadFile($sourcePath, $destination)
    {
        $url = $this->url . '' . ltrim($sourcePath, '/');
		$headers = [];
        if (!empty($this->token)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }		
        $write = fopen($destination, 'w+b');
        //Here is the file we are downloading, replace spaces with %20
        $ch = curl_init(str_replace(' ', '%20', $url));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        // write curl response to file
        curl_setopt($ch, CURLOPT_FILE, $write);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // get curl response
        curl_exec($ch);
        curl_close($ch);
        fclose($write);

        return (file_exists($destination));
    }

    /**
     * Takes an array of fields and makes a string from them for passing in cURL
     *
     * @param array $fields the fields to urlify
     * @return string
     * @access public
     */
    public function urlify($fields)
    {
        $fieldsString = '';
        foreach ($fields as $key => $value) {
            $fieldsString .= $key.'='.$value.'&';
        }
        return rtrim($fieldsString, '&');
    }

}
