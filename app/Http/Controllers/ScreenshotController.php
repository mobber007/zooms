<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Webshot;

class ScreenshotController extends Controller
{

    public function index(Request $request)
    {
        $webshot = new Webshot();


        switch($request->size)
        {
            case 'cover';
               $screenshot = $webshot->take($request->url, "full");
                break;
            case 'full';
               $screenshot = $webshot->take($this->site_data->url, "cover");
                break;
        }

        echo "<img src=$screenshot style='margin: 0 auto'>";
    }


}
