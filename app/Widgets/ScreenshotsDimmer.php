<?php

namespace App\Widgets;

use Illuminate\Support\Facades\Auth;
use App\Request;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Widgets\BaseDimmer;
use Illuminate\Support\Facades\Storage;

class ScreenshotsDimmer extends BaseDimmer
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
        $count = count(Storage::disk('public')->files('screenshots'));
        $string = trans_choice('Screenshots', $count);

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-camera',
            'title'  => "{$count} {$string}",
            'text'   => 'You have '.$count.' '.Str::lower($string).' in your storage.Click on button below to view media.',
            'button' => [
                'text' => __('View screenshots'),
                'link' => route('voyager.media.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/02.jpg'),
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
