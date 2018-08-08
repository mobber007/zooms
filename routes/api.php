<?php

use Illuminate\Http\Request;

Route::domain(env('API_DOMAIN'))->group(function () {

    Route::get('/fb', 'SiteStatsController@update_facebook_stats')->name('api.fb.stats');
    Route::get('/tw', 'SiteStatsController@update_twitter_stats')->name('api.tw.stats');
    Route::get('/seo', 'SiteStatsController@update_seo_stats')->name('api.details.stats');



    Route::get('/screenshot', 'ScreenshotController@index')->name('api.site.screenshot');

    Route::get('/get/{url?}', 'RestController@zoom_scrapper')->where('url', '(.*)')->name('api.site.rest')->middleware('check.key');
    Route::get('/update/{url?}', 'ZoomUpdateController@zoom_scrapper')->where('url', '(.*)')->name('api.site.update');
});

Route::get('/ip/{ip}', 'IpController@show');
Route::get('/ip', 'IpController@index');
