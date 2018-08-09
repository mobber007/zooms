<?php

namespace App\Widgets;

use Illuminate\Support\Facades\Auth;
use App\Request;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Widgets\BaseDimmer;

class RequestDimmer extends BaseDimmer
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        $count = Request::all()->count();
        $string = trans_choice('Requests', $count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-external',
            'title'  => "{$count} {$string}",
            'text'   => 'You have '.$count.' '.Str::lower($string).' in your database.Click on button below to view all requests.',
            'button' => [
                'text' => __('View requests'),
                'link' => route('voyager.requests.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/03.jpg'),
        ]));
    }

    /**
     * Determine if the widget should be displayed.
     *
     * @return bool
     */
    public function shouldBeDisplayed()
    {
        return true;
    }
}
