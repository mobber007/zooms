<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'code',
        'iso_code_2',
        'iso_code_3',
        'iso_country',
        'country',
        'lat',
        'lon'
    ];

    public static function get_iso($country)
    {
        return Country::where('country', '=', $country)->pluck('iso_code_2');
    }
}
