<?php

namespace App\Widgets;

use App\Models\Ip;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Widgets\BaseDimmer;

class IpsDimmer extends BaseDimmer
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
        $count = Ip::all()->count();
        $string = trans_choice('Ips', $count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-lab',
            'title'  => "{$count} {$string}",
            'text'   => 'You have '.$count.' '.Str::lower($string).' in your database.Click on button below to view all ips.',
            'button' => [
                'text' => __('View ips'),
                'link' => route('voyager.ips.index'),
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
