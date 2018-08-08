<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp;

class SiteStatsController extends RestController
{
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
    public function validate_url($url)
    {
        $this->guzzle_client = new GuzzleHttp\Client($this->guzzle_options);
        try
        {
            $res = $this->guzzle_client->request('GET', $url);
            if($res->getHeaderLine('X-Guzzle-Redirect-History'))
            {

                return $res->getHeaderLine('X-Guzzle-Redirect-History');


            }
            else
            {
                return $url;

            }
        }
        catch (\GuzzleHttp\Exception\RequestException $guzzleException)
        {
            return $guzzleException->getCode();

        }
    }



    public function update_facebook_stats(Request $request)
    {
        $this->guzzle_client = new GuzzleHttp\Client($this->guzzle_options);

        $fbId = $request->get('fbid', null);

        if($fbId)
        {
            if($this->facebook_stats_wrapper($fbId, false))
                return response()->json([
                    'facebook_reach'    => $this->facebook_stats_wrapper($fbId, false),
                    'message'    => "Success"
                ], 200);
            else
                return response()->json([
                    'facebook_reach'    => 0,
                    'message'    => "Could not find this id in Facebook Graph API"
                ], 200);

        }
        else
            return response()->json([
                'facebook_reach'    => 0,
                'message'    => "Please enter a Facebook ID"
            ], 200);


    }

    public function update_twitter_stats(Request $request)
    {
        $this->guzzle_client = new GuzzleHttp\Client($this->guzzle_options);
        $twitterId = $request->get('twid');
        if($twitterId)
        {
            if($this->twitter_stats_wrapper($twitterId, false))
                return response()->json([
                    'twitter_reach'    => $this->twitter_stats_wrapper($twitterId, false),
                    'message'    => "Success"
                ], 200);
            else
                return response()->json([
                    'twitter_reach'    => 0,
                    'message'    => "Could not find this id in Twitter Graph API"
                ], 200);
        }
        else
            return response()->json([
                'twitter_reach'    => 0,
                'message'    => "Please enter a Twitter ID"
            ], 200);


    }

    public function update_seo_stats(Request $request)
    {
        $url = $request->get('url', null);


        if($this->validate_url($url))
        {

            return response()->json([
                'domain_authority' => intval(\SEOstats\Services\Mozscape::getDomainAuthority($url)),
                'alexa_rank' => intval(\SEOstats\Services\Alexa::getGlobalRank($url)),

            ]);
        }
        else
            return response()->json([
                'error' => "The URL can't be resolved to an address. Try using a valid URL",
            ]);
    }

}
