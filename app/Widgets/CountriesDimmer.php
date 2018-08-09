<?php

namespace App\Widgets;

use App\Models\Country;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Widgets\BaseDimmer;

class CountriesDimmer extends BaseDimmer
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
        $count = Country::all()->count();
        $string = trans_choice('Countries', $count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-world',
            'title'  => "{$count} {$string}",
            'text'   => 'You have '.$count.' '.Str::lower($string).' in your database.Click on button below to view all countries.',
            'button' => [
                'text' => __('View countries'),
                'link' => route('voyager.countries.index'),
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
