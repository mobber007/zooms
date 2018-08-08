<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ip extends Model
{
    protected $fillable = [
        'ip',
        'country',
    ];
    protected $primaryKey = 'ip'; // or null

    public $incrementing = false;
}
