<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use \GuzzleHttp\Client as Client;
use Spatie\Browsershot\Browsershot;

class Webshot extends Browsershot
{

    public function take($url, $size)
    {
        $shot_type = "write";

        switch($size)
        {
            case 'cover';
                $shot = $this->url($url)
                    ->dismissDialogs()
                    ->waitUntilNetworkIdle()
                    ->timeout(120)
                    ->ignoreHttpsErrors()
                    ->windowSize(1920, 1080)
                    ->setScreenshotType('jpeg', 100)
                    ->screenshot();
                $shot_type = "main";
                break;
            case 'full';
                $shot = $this->url($url)
                    ->dismissDialogs()
                    ->timeout(120)
                    ->waitUntilNetworkIdle()
                    ->ignoreHttpsErrors()
                    ->windowSize(1920, 1080)
                    ->setScreenshotType('jpeg', 100)
                    ->fullPage()
                    ->screenshot();
                $shot_type = "write";
                break;
        }



        $path = 'screenshots/'.$this->shot_name($url).'-'.$shot_type.'.jpg';

        $disk = Storage::disk('public');

        $disk->put($path, $shot);

        $url = $disk->url($path);

        return $url;
    }

    protected function shot_name($url)
    {
        $pieces = parse_url($url);
        $domain = isset($pieces['host']) ? $pieces['host'] : '';
        if(preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)){
            return str_replace('.','-',$regs['domain']);
        }
        return $url;
    }

}
