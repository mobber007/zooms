<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use GuzzleHttp;
use Goutte\Client;
use diversen\meta;
use App\Models\Country;
use Illuminate\Support\Facades\DB;



class ZoomUpdateController extends Controller
{

    public $site_data;
    public $guzzle_client;
    public $guzzle_options = [
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


    public function zoom_scrapper($url, Request $request)
    {
        $this->guzzle_client = new GuzzleHttp\Client($this->guzzle_options);
        $this->site_data = new \stdClass();
        $validated = $this->validation_wrapper($url);

        $fields = explode(',',$request->get('fields', 'all'));

        if(count($fields) > 0)
        {
            $count = 0;
            foreach ($fields as $field)
            {

                    if($field == 'all')
                    {

                        if ($validated !== 200) {
                            return response()->json([
                                'success' => false,
                                'message' => "The data could not be updated",
                                'status' => $validated,
                                'jumps' => 0,
                                'site' => $this->site_data
                            ]);
                        } else {
                            if (isset($this->site_data->url) && isset($this->site_data->hostname) && isset($this->site_data->secured))
                            {
                                $this->site_data->ip_address = gethostbyname($this->site_data->hostname);
                                $make_country = DB::select($this->inet_aton_sql($this->site_data->ip_address))[0]->country;
                                if ($make_country)
                                    $this->site_data->country_name = $make_country;
                                $this->site_data->country = Country::get_iso($make_country)[0];

                                $email = $this->supercharged_email_scrapper($this->site_data->url.$this->site_data->submission_page);

                                $this->site_data->email_deliverable = $this->email_verification_wrapper($email);
                                if($this->site_data->email_deliverable)
                                {
                                    $this->site_data->contact_email = $email;

                                }
                                else
                                {
                                    $this->site_data->contact_email = null;
                                };



                                $this->site_data->domain_authority = intval(\SEOstats\Services\Mozscape::getDomainAuthority($this->site_data->url));
                                $this->site_data->alexa_rank = intval(\SEOstats\Services\Alexa::getGlobalRank($this->site_data->url));

                                $count = $this->supercharged_social_scrapper();


                                $m = new meta();
                                $parsed_meta = $m->getMeta($url);

                                foreach (array_keys($parsed_meta) as $key) {

                                    $key_helper = 'meta_' . $key;
                                    $this->site_data->$key_helper = $parsed_meta[$key];

                                }
                                $this->site_data->meta_keywords = $this->categories_links_scrapper_wrapper($this->site_data->url);
                            }
                        }
                    }
                    if($field == 'social')
                    {
                        if($request->fbid)
                        {
                            $this->site_data->facebook_page = $request->fbid;
                            $this->site_data->facebook_reach  = $this->facebook_stats_wrapper($this->site_data->facebook_page, true);
                        }
                        if($request->twid)
                        {
                            $this->site_data->twitter_page = $request->twid;
                            $this->site_data->twitter_reach  = $this->twitter_stats_wrapper($this->site_data->twitter_page, true);
                        }
                        if(!isset($this->site_data->facebook_page) || !isset($this->site_data->twitter_page))

                        {
                            $count = $this->supercharged_social_scrapper();

                        }

                        $this->site_data->domain_reach = 0;


                        if (isset($this->site_data->facebook_reach))
                            $this->site_data->domain_reach = $this->site_data->domain_reach + $this->site_data->facebook_reach;

                        if (isset($this->site_data->twitter_reach))
                            $this->site_data->domain_reach = $this->site_data->domain_reach + $this->site_data->twitter_reach;

                    }
                    if($field == 'email')
                    {
                        if($request->contact_email)
                        {
                            $this->site_data->email_deliverable = $this->email_verification_wrapper($request->contact_email);
                            if($this->site_data->email_deliverable)
                            {
                                $this->site_data->contact_email = $request->contact_email;

                            }
                            else
                            {
                                $this->site_data->contact_email = null;
                            }
                        }
                        else
                        {
                            $email = $this->supercharged_email_scrapper($this->site_data->url);

                            $this->site_data->email_deliverable = $this->email_verification_wrapper($email);
                            if($this->site_data->email_deliverable)
                            {
                                $this->site_data->contact_email = $email;

                            }
                            else
                            {
                                $this->site_data->contact_email = null;
                            }
                        }

                    }
                    if($field == 'seo')
                    {
                        $this->site_data->domain_authority = intval(\SEOstats\Services\Mozscape::getDomainAuthority($this->site_data->url));
                        $this->site_data->alexa_rank = intval(\SEOstats\Services\Alexa::getGlobalRank($this->site_data->url));
                    }
                    if($field == 'keywords')
                    {
                        $this->site_data->meta_keywords = $this->categories_links_scrapper_wrapper($this->site_data->url);
                    }
                    if($field == 'location')
                {
                    $this->site_data->ip_address = gethostbyname($this->site_data->hostname);
                    $make_country = DB::select($this->inet_aton_sql($this->site_data->ip_address))[0]->country;
                    if ($make_country)
                        $this->site_data->country_name = $make_country;
                    $this->site_data->country = Country::get_iso($make_country)[0];
                }

            }
            return response()->json([
                'success' => true,
                'message' => "Successfully scrapped updated data",
                'status' => $validated,
                'jumps' => $count,
                'site' => $this->site_data
            ]);

        }
        else
        {
            return response()->json([
                'success' => false,
                'message' => "The update fields are missing",
                'status' => 404,
                'jumps' => 0,
                'site' => $this->site_data
            ]);
        }


    }




    public function make_pretty_hostname($url)
    {
        $pieces = parse_url($url);
        $domain = isset($pieces['host']) ? $pieces['host'] : '';
        if(preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)){
            return $regs['domain'];
        }
        return $url;

    }

    protected function inet_aton_sql($ip)
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



    protected function supercharged_email_scrapper($url)
    {
        $client = new Client();
        $client->setClient($this->guzzle_client);
            $res = $client->request('GET', $url);
            $str = $res->filterXPath('descendant-or-self::html')->html();


        $regexp = '/([a-z0-9_\.\-])+(\@|\[at\]|\(at\))+(([a-z0-9\-])+\.|\[dot\]|\(dot\))+([a-z0-9]{2,4})+/i';;
        preg_match_all($regexp, $str, $m);

        $similar_array =  array();


        $datas = array_reverse(array_unique(isset($m[0]) ? $m[0] : array()));

        foreach ($datas as $data)
        {
            similar_text($this->site_data->name, $data, $percent);
            $similar_array[preg_replace('(\@|\[at\]|\(at\))','@',$data)] = $percent;
        }
        if(count($similar_array) > 0)
         return array_search(max($similar_array), $similar_array);
        else
            return null;
    }
    protected function email_verification_wrapper($email)
    {
        $full = "http://emailverifier.outreach.buzz:8080/v1/json/{$email}";

        try
        {
            $res = $this->guzzle_client->request('GET', $full);
            $data = json_decode($res->getBody());
            if(isset($data->deliverable))
                return $data->deliverable;
            else
                return false;

        }
        catch (GuzzleHttp\Exception\RequestException $guzzleException)
        {
            return false;
        }
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

                $parsed_url->secured = $parsed_data[$key];
            }

            if($key === "host")
            {
                if($this->make_pretty_hostname($url))
                {
                    $key_helper = 'hostname';

                    $parsed_url->$key_helper = $this->make_pretty_hostname($url);

                    $parsed_url->name = strtoupper(explode('.',$this->make_pretty_hostname($url))[0]);
                }
            }
            if($key === "path")
            {
                $key_helper = "submission_page";
                $parsed_url->$key_helper = $parsed_data[$key];
            }


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
                if(isset($this->site_data->submission_page) && $this->site_data->submission_page !== '/')
                    $this->site_data->url =  str_replace($this->site_data->submission_page, "", $res->getHeaderLine('X-Guzzle-Redirect-History'));
                else
                    $this->site_data->url = $res->getHeaderLine('X-Guzzle-Redirect-History');


            }
            else
            {
                $this->site_data = $this->make_parsed_std($url);
                if(isset($this->site_data->submission_page) && $this->site_data->submission_page !== '/')
                    $this->site_data->url =  str_replace($this->site_data->submission_page, "", $url);
                else
                    $this->site_data->url = $url;

            }

            return $res->getStatusCode();

        }
        catch (GuzzleHttp\Exception\RequestException $guzzleException)
        {
            return $guzzleException->getCode();

        }
    }



    protected function seo_stats_wrapper($url)
    {
        $this->site_data->domain_authority =  intval(\SEOstats\Services\Mozscape::getDomainAuthority($url));
        $this->site_data->alexa_rank = intval(\SEOstats\Services\Alexa::getGlobalRank($url));
    }

    protected function shared_count_wrapper($url)
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

    protected function email_hunter_wrapper($hostname)
    {
        $full = "https://api.hunter.io/v2/domain-search?domain={$hostname}&api_key=c3b4046b6a3f37bc2a43743f61bca0328d9b18d3";

        try
        {
            $res = $this->guzzle_client->request('GET', $full);
            $data = json_decode($res->getBody());
            if(isset($data->data->emails))
                if(count($data->data->emails) > 0)

                    return $data->data->emails[0]->value;
                else
                    return null;

        }
        catch (GuzzleHttp\Exception\RequestException $guzzleException)
        {
            return null;
        }
    }




    protected function facebook_stats_wrapper($facebook_page, $check)
    {
        $api_key = "access_token=426444987790314|-c8GjulTtSpfpM75FEJlV_9ENBY";
        $full ="https://graph.facebook.com/{$facebook_page}/?fields=fan_count,website&{$api_key}";
        try
        {
            $res = $this->guzzle_client->request('GET', $full);
            $data = json_decode($res->getBody());
            if($check == true)
                if(isset($data->website))
                {
                    similar_text($this->make_pretty_hostname($data->website), $this->site_data->hostname, $percent);
                    $this->site_data->fb_probability = intval($percent);
                }
            return $data->fan_count;

        }
        catch (GuzzleHttp\Exception\RequestException $guzzleException)
        {
            return 0;
        }
    }

    protected function twitter_stats_wrapper($twitter_page, $check)
    {

        $client = new Client();
        $client->setClient($this->guzzle_client);

        $url = 'https://twitter.com/'.$twitter_page;

        try {

            $res = $client->request('GET', $url);

            $datas = array_reverse(array_unique(array_filter($res->filterXPath('descendant-or-self::a[contains(@data-nav, "followers")]/span')->evaluate('substring-after(@data-count, "")'))));

            if($check == true)
            {
                $webs = array_reverse(array_unique(array_filter($res->filterXPath('descendant-or-self::span[contains(@class, "ProfileHeaderCard-urlText")]/a')->evaluate('substring-after(@title, "")'))));
                if (count($webs) > 0) {
                    similar_text($this->make_pretty_hostname($webs[0]), $this->site_data->hostname, $percent);
                    $this->site_data->tw_probability = intval($percent);
                }
            }

            if(count($datas) > 0)
                return intval($datas[0]);


        } catch (GuzzleHttp\Exception\RequestException $guzzleException) {
            return 0;
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

                    if(!isset($this->site_data->twitter_page))
                        if($data[0] == 'twitter:site')
                        {

                            if($this->twitter_stats_wrapper($data[1], true) > 0)
                            {
                                $this->site_data->twitter_page = str_replace_first('@', '', $data[1]);
                                $this->site_data->twitter_reach = $this->twitter_stats_wrapper($data[1], true);
                            }
                        }
                    if(!isset($this->site_data->facebook_page))
                        if($data[0] == 'article:publisher')
                        {
                            if($this->facebook_stats_wrapper(str_replace_first('/', '', str_replace_first('@', '', explode('facebook.com/', $data[1])[count(explode('facebook.com/', $data[1])) - 1])), true))
                            {
                                $this->site_data->facebook_page = str_replace_first('/', '', str_replace_first('@', '', explode('facebook.com/', $data[1])[count(explode('facebook.com/', $data[1])) - 1]));
                                $this->site_data->facebook_reach = $this->facebook_stats_wrapper(str_replace_first('/', '', str_replace_first('@', '', explode('facebook.com/', $data[1])[count(explode('facebook.com/', $data[1])) - 1])), true);
                            }
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
            if(!isset($this->site_data->facebook_page))
            {
                $similar_array = array();
                foreach($datas as $data)
                {
                    if(isset($data))
                    {

                        if(strpos($data, 'facebook.com/') !== false && strpos($data, 'facebook.com/share') === false)
                        {
                            if(strpos($data, 'facebook.com/pages/') === false)
                            {
                                similar_text($this->site_data->hostname, str_replace('/','',explode('facebook.com/', $data)[count(explode('facebook.com/', $data)) - 1]), $percent);
                                $similar_array[str_replace('/','',explode('facebook.com/', $data)[count(explode('facebook.com/', $data)) - 1])] = $percent;
                            }
                            else
                            {
                                similar_text($this->site_data->hostname, str_replace('/','',explode('/', $data)[count(explode('/', $data)) - 1]), $percent);
                                $similar_array[str_replace('/','',explode('/', $data)[count(explode('/', $data)) - 1])] = $percent;
                            }
                        }
                    }

                }
                if(count($similar_array))
                    $this->site_data->facebook_page = str_replace_first('/', '', str_replace_first('@', '', array_search(max($similar_array), $similar_array)));

            }
            if(!isset($this->site_data->twitter_page))
            {
                $similar_array = array();
                foreach($datas as $data)
                {
                    if(isset($data))
                    {

                        if(strpos($data, 'twitter.com/') !== false && strpos($data, 'twitter.com/share') !== true)
                        {
                            similar_text($this->site_data->hostname, str_replace('/','',explode('twitter.com/', $data)[count(explode('twitter.com/', $data)) - 1]), $percent);
                            $similar_array[str_replace('/','',explode('twitter.com/', $data)[count(explode('twitter.com/', $data)) - 1])] = $percent;

                        }
                    }
                }
                if(count($similar_array))
                    $this->site_data->twitter_page = str_replace_first('/', '', str_replace_first('@', '', array_search(max($similar_array), $similar_array)));
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



    protected function supercharged_social_scrapper()
    {
        $count = 0;
        $this->meta_social_scrapper_wrapper($this->site_data->url);
        $commons = $this->common_links_scrapper_wrapper($this->site_data->url);
        $links = $commons;
       /* $this->site_data->common_links = $commons;*/
        if (count($links) > 0) {
            foreach ($links as $common_link) {

                if (!isset($this->site_data->facebook_page) || !isset($this->site_data->twitter_page)) {
                    $this->meta_social_scrapper_wrapper($common_link);
                    $count = $count + 1;
                }


            }
            if (!isset($this->site_data->facebook_page) || !isset($this->site_data->twitter_page)) {
                foreach ($links as $common_link) {
                    if (!isset($this->site_data->facebook_page) || !isset($this->site_data->twitter_page)) {
                        $this->similar_social_scrapper_wrapper($common_link);
                        $count = $count + 1;
                    }
                }
            }

        }

        if (isset($this->site_data->facebook_page)) {
            $this->site_data->facebook_reach = $this->facebook_stats_wrapper($this->site_data->facebook_page, true);
        }
        if (isset($this->site_data->twitter_page)) {
            $this->site_data->twitter_reach = $this->twitter_stats_wrapper($this->site_data->twitter_page, true);
        }

        $this->site_data->domain_reach = 0;


        if (isset($this->site_data->facebook_reach))
            $this->site_data->domain_reach = $this->site_data->domain_reach + $this->site_data->facebook_reach;

        if (isset($this->site_data->twitter_reach))
            $this->site_data->domain_reach = $this->site_data->domain_reach + $this->site_data->twitter_reach;

        return $count;
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
            return [];
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
                $tags_string = '';
                foreach ($datas as $data) {
                    if (isset($data)) {

                        if (strpos($data, $url) !== false) {
                            if(strpos($data, 'categor') !== false  || strpos($data, 'topic') !== false  || strpos($data, 'tag') !== false)
                            {
                                similar_text($data, $url , $percent);
                                if($percent > 50)
                                {
                                    $categories_array[$data] = $percent;
                                    $new_tag_cloud = array_reverse(array_values(array_filter(str_replace(',',' ',str_replace(',,','',preg_replace('/[^A-Za-z0-9\-]/',',',(explode($this->site_data->hostname, str_replace($this->site_data->secured,'', $data)))))))));
                                    if(array_filter(explode(' ',$new_tag_cloud[0])))
                                    {
                                        foreach (array_values(array_sort(array_filter(explode(' ',$new_tag_cloud[0])))) as $tag)
                                            if(strpos($tag, 'categor') === false )
                                            {
                                                if(strpos($tag, 'tag') === false)
                                                {
                                                    array_push($tag_cloud, $tag);
                                                }

                                            }
                                            else
                                                if(strpos($tag, 'tag') === false)
                                                {
                                                    if(strpos($tag, 'categor') === false)
                                                    {
                                                        array_push($tag_cloud, $tag);
                                                    }
                                                }

                                    }

                                }
                            }
                        }
                    }
                }
                foreach (array_reverse(array_unique($tag_cloud)) as $single_tag)
                {
                    $tags_string = $tags_string.','.$single_tag;
                }

                return $tags_string;

            }


        } catch (GuzzleHttp\Exception\RequestException $guzzleException) {
            return '';
        }
    }
}
