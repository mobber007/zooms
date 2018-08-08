<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use \GuzzleHttp\Client as Client;

class ScreenshotController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */


    /**
     * Screeenly API Key
     * @var string
     */
    protected $key;

    /**
     * Response from Screeenly
     * @var object
     */
    protected $response;

    /**
     * Request URL for Screenshot
     * @var string
     */
    protected $url;

    /**
     * Height
     * @var integer
     */
    protected $height;

    /**
     * Width
     * @var integer
     */
    protected $width = 1024;

    /**
     * Local Filename
     * @var string
     */
    public $localFilename;


    public $localStoragePath;

    public $apiUrl = 'http://screeenly.com/api/v1/fullsize';

    public function __construct()
    {
        $this->key = env('SCREENSHOT');
        $this->apiUrl = env('SCREENLY_API_URL');
    }

    public function index(Request $request)
    {


        switch($request->size)
        {
            case 'cover';
                $this->setHeight(1080);
                $this->setWidth(1920);

                break;
            case 'full';
                $this->setWidth(1920);
                break;
        }

        $screenshot = $this->capture($request->url);

        $url = $this->store();

        echo "<img src=$url style='margin: 0 auto'>";
    }

    public function capture($url)
    {
        $this->setUrl($url);

        $client = new Client();
        $response = $client->post($this->apiUrl, ['form_params' =>
            [
                'key'    => $this->key,
                'url'    => $url,
                'width'  => $this->width,
                'height' => $this->height
            ]
        ]);

        $responseJson =  $response->getBody();
        $responseArray = json_decode($responseJson);

        $this->response = $responseArray;

        return $this;
    }

    public function store()
    {
        $data = file_get_contents($this->getPath());
        $fileName = $this->make_pretty_screenshot_name($this->url);
        $path = 'screenshots/'.$fileName.'-'.$this->width.'x'.$this->height.'.jpg';

        $disk = Storage::disk('public');

        $disk->put($path, $data);

        $url = $disk->url($path);

        return $url;
    }


    public function make_pretty_screenshot_name($url)
    {
        $pieces = parse_url($url);
        $domain = isset($pieces['host']) ? $pieces['host'] : '';
        if(preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)){
            return str_replace('.','-',$regs['domain']);
        }
        return $url;

    }


    public function setUrl($url)
    {
        return $this->url = $url;
    }

    public function setHeight($height)
    {
        if (is_numeric($height) === false) {
            throw new \Exception('Height must be numeric', 1);
        }
        return $this->height = $height;
    }


    public function setWidth($width)
    {
        if (is_numeric($width) === false) {
            throw new \Exception('Width must be numeric', 1);
        }
        return $this->width = $width;
    }


    public function getPath()
    {
        return $this->response->path;
    }

    public function getBase64()
    {
        return $this->response->base64;
    }

    public function setLocalStoragePath($path)
    {
        return $this->localStoragePath = $path;
    }

    public function setLocalFilename($filename)
    {
        return $this->localFilename = $filename;
    }


}
