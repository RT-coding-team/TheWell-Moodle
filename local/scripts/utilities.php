<?php
/**
 * Various utility functions
 */
/**
 * Login the account and download the given file
 * 
 * @param string $siteUrl   The URL for the website
 * @param string $username  The username to log into
 * @param string $password  The password of the given user
 * @param string $remoteFile    The remote file to download
 * @param string $localFile The local file where to store the contents
 * 
 * @return void
 */
function scripts_curl_download_file($siteUrl, $username, $password, $remoteFile, $localFile) {
    $cookieFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cookies.txt';
    $tokenPattern = '/<input(?:.*?)name=\"logintoken\"(?:.*)value=\"([^"]+).*>/i';
    /**
     * Setup cURL. Since we need to get the logintoken, then login the user, and then get the file,
     * we need to use the same initialized cURL instance.  It will fail otherwise.
     */
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($curl, CURLOPT_COOKIESESSION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_AUTOREFERER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    /**
     *  First retrieve the logintoken
     */
    curl_setopt($curl, CURLOPT_URL, $siteUrl . '/login/index.php');
    curl_setopt($curl, CURLOPT_POST, false);
    $content = curl_exec($curl);
    preg_match($tokenPattern, $content, $matches);
    if (count($matches) <= 1) {
        echo "Unable to get the login token. \r\n";
        exit;
    }
    $payload = [
        'username'      =>  $username,
        'password'      =>  $password,
        'logintoken'    =>  $matches[1]
    ];
    curl_setopt($curl, CURLOPT_URL, $siteUrl . '/login/index.php');
    curl_setopt($curl, CURLOPT_POST, true);
    /**
     * @link https://stackoverflow.com/a/15023426
     */
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payload, '', '&'));
    curl_exec($curl);

    // get the file
    curl_setopt($curl, CURLOPT_URL, $remoteFile);
    curl_setopt($curl, CURLOPT_POST, false);
    $contents = curl_exec($curl);
    file_put_contents($localFile, $contents);
    curl_close($curl);
}