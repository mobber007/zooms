<?php

namespace App\Http\Controllers;

use App\Models\Ip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IpController extends Controller
{

    public function index(Request $request)
    {
            $ip = $request->ip();

            $countryName =  DB::select($this->inet_aton_sql($ip));


            return response()->json([
                'country' => $countryName[0]->country,
                'ip' => $ip
            ]);


    }

    public function show($ip)
    {

        try
        {
            $countryName =  DB::select($this->inet_aton_sql($ip));
            return response()->json([
                'country' => $countryName[0]->country,
                'ip' => $ip
            ]);
        }
        catch (\Exception $ex)
        {
            return response()->json([
                'country' => "Unavailable",
                'ip' => $ip
            ]);
        }

    }

    private function inet_aton_sql($ip)
    {
        $sql = 'SELECT 
	            c.country 
	        FROM 
	            countries c,
	            ips i 
	        WHERE 
	            i.ip < INET_ATON("'.$ip.'") 
	            AND 
	            c.code = i.country 
	        ORDER BY 
	            i.ip DESC 
	        LIMIT 0,1';

        return $sql;
    }
}
