<?php

namespace App\Http\Controllers;


use App\Http\Requests\CollectSiteStats;
use Mockery\Exception;
use Illuminate\Http\Request;
use GuzzleHttp;
use Goutte\Client;
use App\Site;
use diversen\meta;
use Illuminate\Support\Facades\DB;



class SiteController extends Controller
{

    private $site_data;
    private $guzzle_client;
    private $guzzle_options = [
        'allow_redirects' => [
            'max'             => 1,        // allow at most 10 redirects.
            'strict'          => true,      // use "strict" RFC compliant redirects.
            'referer'         => true,      // add a Referer header
            'protocols'       => ['https', 'http'],
            'track_redirects' => true
        ],
        'timeout' => 5,
        'verify' => true,
        'synchronous' => true
    ];
    protected function collectSiteData(Request $request)
    {
        $this->guzzle_client = new GuzzleHttp\Client($this->guzzle_options);
        $url = $request->get('url', null);
        $this->site_data = new \stdClass();
        $validated = $this->validation_wrapper($url);

        $count = 0;
            if($validated) {
                if (isset($this->site_data->main))
                {

                    $this->site_data->ip_address = gethostbyname($this->site_data->host);
                    $this->site_data->country = DB::select($this->inet_aton_sql($this->site_data->ip_address))[0]->country;

                    $this->meta_social_scrapper_wrapper($this->site_data->main);
                    $commons = $this->common_links_scrapper_wrapper($this->site_data->main);
                    $categories_links = $this->categories_links_scrapper_wrapper($this->site_data->main);
                    $this->site_data->commons = $commons;
                    $this->seo_stats_wrapper($this->site_data->main);
                    $this->site_data->domain_reach = $this->sharedcount_wrapper($this->site_data->main);
                    $links = array_merge($commons, $categories_links);
                    if (count($links) > 0) {
                        foreach ($links as $common_link) {

                            if (!isset($this->site_data->facebook_handle) || !isset($this->site_data->twitter_handle)) {
                                $this->meta_social_scrapper_wrapper($common_link);
                                $count = $count + 1;
                            }


                        }
                        if (!isset($this->site_data->facebook_handle) || !isset($this->site_data->twitter_handle)) {
                            foreach ($links as $common_link) {
                                if (!isset($this->site_data->facebook_handle) || !isset($this->site_data->twitter_handle)) {
                                    $this->similar_social_scrapper_wrapper($common_link);
                                    $count = $count + 1;
                                }
                            }
                        }

                    }

                    if (isset($this->site_data->facebook_handle)) {
                        $this->site_data->facebook_reach = $this->facebook_stats_wrapper($this->site_data->facebook_handle);
                    }
                    if (isset($this->site_data->twitter_handle)) {
                        $this->site_data->twitter_reach = $this->twitter_stats_wrapper($this->site_data->twitter_handle);
                    }


                }

            }

        return response()->json([
            'site' => $this->site_data,
            'page_jumps' => $count,
        ]);




       /* if($this->validateURL($request->get('url')))
        {
            $protocolHelper = $this->formProtocol($url);
            $url = $protocolHelper['redirect'];

            $mainURL = parse_url($protocolHelper['redirect']);

            $scheme = null;
            $host = null;
            $path = null;

            if (array_key_exists('path', $mainURL)) {
                $path = $mainURL ['path'];
            }



            if($path)

            {
                $urlData = str_replace($path,"", $url);
                $submission_page = $path;
            }
            else
            {
                $urlData = $url;
                $submission_page = "#";
            }

            if($protocolHelper['port'] == 443)
            {


                $ssl_secured = true;
            }
            else
            {
                $ssl_secured = false;
            }

            // Collect HostName

            $hostname = $this->collectHostnameData($urlData);

            $hostnameData = $hostname;

            $siteName = $this->setSiteName($hostname);

            $contact_emailData = $this->collectEmailData($url,$hostname);

            $facebook_pageData = $this->getFacebookPage($urlData, $hostname);
            $facebook_reach = $this->getFacebookStats($facebook_pageData);

            $twitter_pageData = $this->getTwitterPage($urlData, $hostname);
            $twitter_reach = $this->getTwitterstats($twitter_pageData);


            // Collect MozScape Data

            $domain_authorityData = $this->collectMozData($urlData);

            // Collect Alexa  Data

            $alexa_rankData = $this->collectAlexaData($urlData);

            $domain_shares = $this->domainShares($urlData);

            // Collect Social Reach Data

            // Collect IP Address

            $ip_addressData = $this->collectIpData($hostname);

            //Collect MetaData

            $metaHelper =$this->getSiteMeta($urlData);



            if (array_key_exists('title', $metaHelper)) {
                $meta_title = $metaHelper ['title'];
            }
            else
                $meta_title = "No meta title";




            if (array_key_exists('description', $metaHelper))
            {
                $meta_description = $metaHelper ['description'];
            }
            else
                $meta_description = "No meta description";





            if (array_key_exists('keywords', $metaHelper)) {
                $meta_keywords = $metaHelper ['keywords'];
            }
            else
                $meta_keywords = "No meta keywords";





            return response()->json([
                'success' => true,
                'message' => "Collected data",
                'name' => $siteName,
                'hostname' => $hostnameData,
                'url' => $urlData,
                'submission_page' => $submission_page,
                'contact_email' => $contact_emailData,
                'facebook_page' => $facebook_pageData,
                'facebook_reach' => $facebook_reach,
                'twitter_page' => $twitter_pageData,
                'twitter_reach' => $twitter_reach,
                'domain_authority' => $domain_authorityData,
                'alexa_rank' => $alexa_rankData,
                'domain_reach' => $domain_shares,
                'ip_address' => $ip_addressData,
                'country' => DB::select($this->inet_aton_sql($ip_addressData))[0]->country,
                'ssl_secured' => $ssl_secured,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'meta_keywords' => $meta_keywords


            ]);

        }
        else
            return response()->json([
                'success' => false,
                'message' => "The URL key is missing or is invalid",
            ]);*/


    }

    public function fullContact($domain)
    {
        $apiKey = "apiKey=3AvJiebH9vo7Ycl2Ozwy1pN92DMUYSxO&domain=";
        $url = "https://api.fullcontact.com/v2/company/lookup.json?";
        $fullURL = $url.$apiKey.$domain;

        $ch = curl_init($fullURL);
        $timeout = 5;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        $f_result = curl_exec($ch);

        $f_obj = json_decode($f_result, true);

        return $f_obj;
    }

    protected function getFacebookStats($fbId)
    {
        $api_key = "access_token=426444987790314|-c8GjulTtSpfpM75FEJlV_9ENBY";



        $fb_count ="https://graph.facebook.com/v2.7/{$fbId}/?fields=fan_count&{$api_key}";

        $ch = curl_init($fb_count);
        $timeout = 5;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        $f_result = curl_exec($ch);

        $f_obj = json_decode($f_result, true);

        if (array_key_exists('fan_count', $f_obj))
        {
            return $f_obj['fan_count'];
        }
        else
            return 0;


    }

    protected function domainShares ($url)
    {
        return $this->sharedcount_wrapper($url);

        /*$ch = curl_init($full);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64; rv:31.0) Gecko/20100101 Firefox/31.0'
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        $obj = json_decode($result, true);

        $total = 0;

        try
        {
            $total = $total + $obj['StumbleUpon'];
        }
        catch (Exception $e)
        {
            $total = $total + 0;
        }
        try
        {
            $total = $total + $obj['Pinterest'];
        }
        catch (Exception $e)
        {
            $total = $total + 0;
        }
        try
        {
            $total = $total + $obj['LinkedIn'];
        }
        catch (Exception $e)
        {
            $total = $total + 0;
        }
        try
        {
            $total = $total + $obj['Facebook']['total_count'];
        }
        catch (Exception $e)
        {
            $total = $total + 0;
        }
        try
        {
            $total = $total + $obj['GooglePlusOne'];
        }
        catch (Exception $e)
        {
            $total = $total + 0;
        }

        return $total;*/
    }

    protected function getTwitterstats($twitterId)
    {

        $url = "https://twitter.com/{$twitterId}";

        if($this->validateURL($url))

        {
            $ch = curl_init();
            $timeout = '5L';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            $html = curl_exec($ch);
            curl_close($ch);

            $winner= null;
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            foreach($dom->getElementsByTagName('a') as $link)
            {
                if ($link->getAttribute('data-nav') == 'followers') {
                    $winner = str_replace(" Followers","", $link->getAttribute('title'));
                    $winner = str_replace(",","", $link->getAttribute('title'));
                    break;
                }
            }
            return intval($winner);
        }

        else
            return 0;
    }

    protected function collectMozData($url)
    {
        // Collect MozScape Domain Authority Data


        if(is_numeric(\SEOstats\Services\Mozscape::getDomainAuthority($url)))

            $helper = \SEOstats\Services\Mozscape::getDomainAuthority($url);
        else
            $helper = 0;

        return intval($helper);
    }
    protected function collectAlexaData($url)
    {
        // Collect Alexa Global Rank Data

        $helper = 0;

        if(is_numeric(\SEOstats\Services\Alexa::getGlobalRank($url)))

            $helper = \SEOstats\Services\Alexa::getGlobalRank($url);

        return intval($helper);
    }
    protected function collectIpData($hostname)
    {
        // Collect IP Data

        $helper = 0;

        if(is_string(gethostbyname($hostname)))

            $helper = gethostbyname($hostname);

        return $helper;
    }

    public function getSiteMeta($url)
    {
        $ary = array();


            if($this->validateURL($url))
            {
                $m = new meta();

                if($m->getMeta($url))

                {
                    $ary = $m->getMeta($url);
                }
                else
                    $ary = array();
            }


        return $ary;

    }

    protected function collectHostnameData($url)
    {

        $host = @parse_url($url, PHP_URL_HOST);

        // If the URL can't be parsed, use the original URL
        // Change to "return false" if you don't want that

        if (!$host)
            $host = $url;

        // The "www." prefix isn't really needed if you're just using
        // this to display the domain to the user

        if (substr($host, 0, 4) == "www.")
            $host = substr($host, 4);

        return $host;
    }

    protected function setSiteName($hostname)
    {
        $hostnameHelper = explode(".", "{$hostname}");
        return mb_strtoupper($hostnameHelper [0]);
    }

    protected function getPageData($url) {
        $ch = curl_init();
        $timeout = 1000;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64; rv:31.0) Gecko/20100101 Firefox/31.0',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    protected function getFacebookPage($url, $hostname)
    {

        $socialPattern = '/\b(?:(?:https?):\/\/|(?:www?)\.)?facebook.com\/([-a-z0-9@#=_!.\/]*[-a-z0-9@!#_\/])/i';

        $socialId = $this->socialIdHelpers($url, $hostname, $socialPattern);
        $socialId = explode("/", $socialId);

        $socialIdHelper = $socialId [0];



        if($this->getFacebookStats($socialIdHelper) > 0)
        {
            return $socialIdHelper;
        }
        else
        {
            return null;
        }





    }

    protected function getTwitterPage($url, $hostname)
    {
        $socialPattern = '/\b(?:(?:https?):\/\/|(?:www?)\.)?twitter.com\/([-a-z0-9@#=_!.\/]*[-a-z0-9@#_|\/])/i';

        $socialId = $this->socialIdHelpers($url, $hostname, $socialPattern);

        $twitterIdHelper = explode("/", $socialId);

        if(count($twitterIdHelper) < 2)

            return $twitterIdHelper[0];

        else
            return $twitterIdHelper[1];

    }
    protected function socialIdHelpers($url, $hostname,$socialPattern)
    {
        $result_data = $this->getPageData($url);

        $siteName = $hostname;

        preg_match_all($socialPattern,
            $result_data,
            $outs, PREG_PATTERN_ORDER);


        $arrayHelps = array();

        if(count($outs) > 1)

        {
            foreach ($outs[1] as $out)
            {

                similar_text($siteName,$out,$percent);

                $arrayHelps [$out] = $percent;


            }
        }


        if(count($arrayHelps) > 0)
        {
            $arrayMax = max($arrayHelps);
            $socialId = array_search($arrayMax, $arrayHelps);
        }
        else
        {
            $socialId = null;
        }


        return $socialId;


    }


    protected function collectSocialReachData($fbId, $twitterId)
    {
        // Collect Social Reach Data

        $helper = 0;

        if(is_numeric($this->getFacebookStats($fbId)))

            $helper = $this->getFacebookStats($fbId);

        if(is_numeric($this->getTwitterStats($twitterId)))

            $helper = $helper + $this->getTwitterStats($twitterId);

        return $helper;
    }

    public function getEmail($url)
    {
        $ch = curl_init(); // initialize curl handle
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,7);
        curl_setopt($ch, CURLOPT_URL,$url); // set url to post to
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
        curl_setopt($ch, CURLOPT_TIMEOUT, 50); // times out after 50s

        $buffer = curl_exec($ch); // run the whole process
        curl_close($ch);

        $file_string = $buffer;
        $email_pattern = "/([\s]*)([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*([ ]+|)@([ ]+|)([a-zA-Z0-9-]+\.)+([a-zA-Z]{2,}))([\s]*)/i";
        preg_match_all($email_pattern,
            $file_string,
            $outs, PREG_PATTERN_ORDER);

        $neaterArray = (array_values(array_unique(str_replace(' ', '', $outs[0]))));
        /*return filter_var($neaterArray[0], FILTER_SANITIZE_EMAIL);*/

        if(count($neaterArray) > 0)
            return filter_var($neaterArray[0], FILTER_SANITIZE_EMAIL);
        else
            return null;

    }

    public function extract_email_address ($webData) {

        $emails[] = [];
        //This function extracts and stroes all e-mails from collected data
        foreach(preg_split('/\s/', $webData) as $token) {
            $email = filter_var(filter_var($token, FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL);
            if ($email !== false) {
                $emails[] = $email;
            }
        }
        return $emails;
    }

    protected function collectEmailData($url,$hostname)
    {
        // Collect Email Data

        $helper = null;

        if(is_string($this->huntEmail($url,$hostname)))
            $helper = $this->huntEmail($url,$hostname);

        return $helper;
    }
    protected function huntEmail($url, $hostname)
    {
        $full = "https://api.hunter.io/v2/domain-search?domain={$hostname}&api_key=c3b4046b6a3f37bc2a43743f61bca0328d9b18d3";

        $ch = curl_init($full);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        $obj = json_decode($result, true);


        if(empty($obj['data']['emails']))

        {
            curl_close($ch);
            return $this->getEmail($url);
        }

        else

        {
            curl_close($ch);
            return $obj['data']['emails'][0]['value'];
        }



    }

    public function validateURL($url)
    {

        $isString = empty($url);

        if(!$isString)
        {
            $handle = curl_init($url);

            curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, TRUE);

            /* Get the HTML or whatever is linked in $url. */

            $response = curl_exec($handle);

            /* Check for response error codes*/

            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            curl_close($handle);

            if ($httpCode > 299)
                return false;
        }
        else
            return false;

        return true;
    }
    public function formProtocol($url)
    {
        try
        {
            $handle = curl_init($url);

            curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);

            /* Get the HTML or whatever is linked in $url. */

            $response = curl_exec($handle);

            /* Check for response error codes*/

            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $redirectURL = curl_getinfo($handle,CURLINFO_EFFECTIVE_URL );
            $port = curl_getinfo($handle,CURLINFO_PRIMARY_PORT );
            $resource['code'] = $httpCode;
            $resource['redirect'] = $redirectURL;
            $resource['port'] = $port;
            curl_close($handle);
            return $resource;

        }
        catch (Exception $e)
        {
            return response()->json([
                'success' => false,
                'message' => "The URL is invalid or can not be followed",
            ]);
        }
    }

    public function realURL($url)
    {

            try
        {


            $handle = curl_init($url);

            curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, TRUE);

            /* Get the HTML or whatever is linked in $url. */

            $response = curl_exec($handle);

            /* Check for response error codes*/

            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            $redirectURL = curl_getinfo($handle,CURLINFO_EFFECTIVE_URL );

            $ssl_cert = curl_getinfo($handle,CURLINFO_SSL_VERIFYRESULT);


            if($ssl_cert == 1 && $httpCode == 0)

            {
                $redirectURL = str_replace('https','http',$redirectURL);
                $httpCode = 1;
            }

            curl_close($handle);

            return $redirectURL;



        }
        catch (Exception $e)
        {
            return null;
        }

    }

    private function inet_aton_sql($ip)
    {
        $sql = 'SELECT 
	            c.country 
	        FROM 
	            countries c,
	            ips i 
	        WHERE 
	            i.ip < INET_ATON("'.$ip.'") 
	            AND 
	            c.code = i.country 
	        ORDER BY 
	            i.ip DESC 
	        LIMIT 0,1';

        return $sql;
    }








    protected function make_parsed_std($url)
    {
        $parsed_url = new \stdClass();
        $parsed_data = parse_url($url);


        foreach (array_keys($parsed_data) as $key)
        {

            if($key === "scheme")
            {
                if($parsed_data[$key] === "https")
                {

                    $parsed_url->ssl_secured = true;
                }
                else {


                    $parsed_url->ssl_secured = false;
                }
                $parsed_url->$key = $parsed_data[$key];
            }
            else
                $parsed_url->$key = $parsed_data[$key];

        }

        return $parsed_url;
    }

    protected function validation_wrapper($url)
    {
        try
        {
            $res = $this->guzzle_client->request('GET', $url);
            if($res->getHeaderLine('X-Guzzle-Redirect-History'))
            {

                $this->site_data = $this->make_parsed_std($res->getHeaderLine('X-Guzzle-Redirect-History'));
                if(isset($this->site_data->path) && $this->site_data->path !== '/')
                    $this->site_data->main =  str_replace($this->site_data->path, "", $res->getHeaderLine('X-Guzzle-Redirect-History'));
                else
                    $this->site_data->main = $res->getHeaderLine('X-Guzzle-Redirect-History');

                $this->site_data->url = $res->getHeaderLine('X-Guzzle-Redirect-History');
                $this->site_data->status = $res->getStatusCode();
            }
            else
            {
                $this->site_data = $this->make_parsed_std($url);
                if(isset($this->site_data->path) && $this->site_data->path !== '/')
                    $this->site_data->main =  str_replace($this->site_data->path, "", $url);
                else
                    $this->site_data->main = $url;

                $this->site_data->url = $url;
                $this->site_data->status = $res->getStatusCode();
            }

            return true;

        }
        catch (GuzzleHttp\Exception\RequestException $guzzleException)
        {
            return response()->json([
                'success' => false,
                'site' => $guzzleException->getCode(),
                'message' => $guzzleException->getMessage(),
            ]);

        }
    }

    protected function seo_stats_wrapper($url)
    {
        $this->site_data->domain_authority =  intval(\SEOstats\Services\Mozscape::getDomainAuthority($url));
        $this->site_data->alex_rank = intval(\SEOstats\Services\Alexa::getGlobalRank($url));
    }

    protected function sharedcount_wrapper($url)
    {
        $api_key = "2dc29680a555b507791ca9d2784512ca776fc007";
        $full = "https://api.sharedcount.com/v1.0/?url={$url}/&apikey={$api_key}";

        try
        {
            $res = $this->guzzle_client->request('GET', $full);
            $data = json_decode($res->getBody());
            return $data->StumbleUpon + $data->Pinterest + $data->LinkedIn + $data->Facebook->total_count + $data->GooglePlusOne;

        }
        catch (GuzzleHttp\Exception\RequestException $guzzleException)
        {
            return 0;
        }
    }

    protected function facebook_stats_wrapper($facebook_handle)
    {
        $api_key = "access_token=426444987790314|-c8GjulTtSpfpM75FEJlV_9ENBY";
        $full ="https://graph.facebook.com/v2.7/{$facebook_handle}/?fields=fan_count&{$api_key}";
        try
        {
            $res = $this->guzzle_client->request('GET', $full);
            $data = json_decode($res->getBody());
            return $data->fan_count;

        }
        catch (GuzzleHttp\Exception\RequestException $guzzleException)
        {
            return 0;
        }
    }


    protected function twitter_stats_wrapper($twitter_handle)
    {

        $client = new Client();
        $client->setClient($this->guzzle_client);

        $url = 'https://twitter.com/'.$twitter_handle;

        try {

            $res = $client->request('GET', $url);

            $datas = array_reverse(array_unique(array_filter($res->filterXPath('descendant-or-self::a[contains(@data-nav, "followers")]/span')->evaluate('substring-after(@data-count, "")'))));

            if(count($datas) > 0)
            return intval($datas[0]);


        } catch (GuzzleHttp\Exception\BadResponseException $guzzleException) {
            return $guzzleException;
        }
    }

    protected function meta_social_scrapper_wrapper($url)
    {

        $client = new Client();
        $client->setClient($this->guzzle_client);

        try
        {
            $res = $client->request('GET', $url);

            $meta_datas = $res->filterXPath('descendant-or-self::meta')->extract(['name', 'property','content']);
            foreach($meta_datas as $data)
            {

                $data = array_values(array_filter($data));

                if(count($data) > 1)
                {

                    if(!isset($this->site_data->twitter_handle))
                    if($data[0] == 'twitter:site')
                    {
                        $this->site_data->twitter_handle = str_replace_first('@', '', $data[1]);
                    }
                    if(!isset($this->site_data->facebook_handle))
                    if($data[0] == 'article:publisher')
                    {
                        $this->site_data->facebook_handle = str_replace_first('/', '', str_replace_first('@', '', explode('facebook.com/', $data[1])[count(explode('facebook.com/', $data[1])) - 1]));
                    }
                }
            }



        }
        catch (GuzzleHttp\Exception\RequestException $guzzleException)
        {
            return response()->json([
                'success' => false,
                'status' => $guzzleException->getCode(),
                'message' => $guzzleException->getMessage(),
            ]);
        }
    }

    protected function similar_social_scrapper_wrapper($url)
    {

        $client = new Client();
        $client->setClient($this->guzzle_client);

        try
        {
            $res = $client->request('GET', $url);
            $datas = array_sort(array_unique($res->filterXPath('descendant-or-self::a')->extract(['href'])));
            if(!isset($this->site_data->facebook_handle))
            {
                $similar_array = array();
                foreach($datas as $data)
                {
                    if(isset($data))
                    {

                        if(strpos($data, 'facebook.com/') !== false && strpos($data, 'facebook.com/share') === false)
                        {
                            similar_text($this->site_data->host, str_replace('/','',explode('facebook.com/', $data)[count(explode('facebook.com/', $data)) - 1]), $percent);
                            $similar_array[str_replace('/','',explode('facebook.com/', $data)[count(explode('facebook.com/', $data)) - 1])] = $percent;
                        }
                    }

                }
                if(count($similar_array))
                $this->site_data->facebook_handle = str_replace_first('/', '', str_replace_first('@', '', array_search(max($similar_array), $similar_array)));

            }
            if(!isset($this->site_data->twitter_handle))
            {
                $similar_array = array();
                foreach($datas as $data)
                {
                    if(isset($data))
                    {

                        if(strpos($data, 'twitter.com/') !== false && strpos($data, 'twitter.com/share') !== true)
                        {
                            similar_text($this->site_data->host, str_replace('/','',explode('twitter.com/', $data)[count(explode('twitter.com/', $data)) - 1]), $percent);
                            $similar_array[str_replace('/','',explode('twitter.com/', $data)[count(explode('twitter.com/', $data)) - 1])] = $percent;

                        }
                    }
                }
                if(count($similar_array))
                $this->site_data->twitter_handle = str_replace_first('/', '', str_replace_first('@', '', array_search(max($similar_array), $similar_array)));
            }


        }
        catch (GuzzleHttp\Exception\RequestException $guzzleException)
        {
            return response()->json([
                'success' => false,
                'status' => $guzzleException->getCode(),
                'message' => $guzzleException->getMessage(),
            ]);
        }
    }




    protected function common_links_scrapper_wrapper($url)
    {

        $client = new Client();
        $client->setClient($this->guzzle_client);

        try {

                $res = $client->request('GET', $url);
                $datas = array_sort(array_unique($res->filterXPath('descendant-or-self::a')->extract(['href'])));
                $common_array = array();
                foreach ($datas as $data) {
                    if (isset($data)) {

                        if (strpos($data, $url) !== false) {
                            if(strpos($data, 'contact') !== false || strpos($data, 'about') !== false || strpos($data, 'write') !== false || strpos($data, 'adverti') !== false || strpos($data, 'guest-post') !== false || strpos($data, 'contribut') !== false)
                            {
                                similar_text($url, $data , $percent);
                                if($percent > 50)
                                $common_array[$data] = $percent;
                            }
                        }
                    }
                }
                return array_keys(array_filter(array_reverse(array_sort($common_array))));




        } catch (GuzzleHttp\Exception\RequestException $guzzleException) {
            return response()->json([
                'success' => false,
                'status' => $guzzleException->getCode(),
                'message' => $guzzleException->getMessage(),
            ]);
        }
    }

    protected function categories_links_scrapper_wrapper($url)
    {

        $client = new Client();
        $client->setClient($this->guzzle_client);

        try {
            if(isset($url))
            {
                $res = $client->request('GET', $url);
                $datas = array_sort(array_unique($res->filterXPath('descendant-or-self::a')->extract(['href'])));
                $categories_array = array();
                $tag_cloud = array();
                foreach ($datas as $data) {
                    if (isset($data)) {

                        if (strpos($data, $url) !== false) {
                            if(strpos($data, 'categor') !== false || strpos($data, 'tag') !== false)
                            {
                                similar_text($data, $url , $percent);
                                if($percent > 50)
                                {
                                    $categories_array[$data] = $percent;
                                    $new_tag_cloud = array_reverse(array_values(array_filter(str_replace(',',' ',str_replace(',,','',preg_replace('/[^A-Za-z0-9\-]/',',',(explode($this->site_data->host, str_replace($this->site_data->scheme,'', $data)))))))));
                                    if(array_filter(explode(' ',$new_tag_cloud[0])))
                                    {
                                        foreach (array_values(array_sort(array_filter(explode(' ',$new_tag_cloud[0])))) as $tag)
                                            if(strpos($tag, 'categor') ===false)
                                            {
                                                array_push($tag_cloud, $tag);
                                            }
                                    }

                                }
                            }
                        }
                    }
                }
                $this->site_data->tag_cloud = array_reverse(array_unique($tag_cloud));
                return array_keys(array_filter(array_reverse(array_sort($categories_array))));

            }


        } catch (GuzzleHttp\Exception\RequestException $guzzleException) {
            return response()->json([
                'success' => false,
                'status' => $guzzleException->getCode(),
                'message' => $guzzleException->getMessage(),
            ]);
        }
    }
}
